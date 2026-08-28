<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Services\ActivityLogger;
use ZipArchive;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Waktu maksimal yang diizinkan untuk membuat backup lokal (misal untuk DB dan file besar).
     * 7200 detik = 2 jam.
     */
    public int $timeout = 7200; 

    public bool $onlyLocal = false;

    public function __construct(bool $onlyLocal = false)
    {
        $this->onlyLocal = $onlyLocal;
    }

    /**
     * Mencegah multiple backup berjalan bersamaan yang bisa menyebabkan
     * direktori temporary bertabrakan / terhapus oleh job lain.
     */
    public function middleware()
    {
        // Job akan di-skip (atau di-release kembali) jika ada job backup lain yang sedang berjalan
        return [new WithoutOverlapping('create_backup')];
    }

    public function handle(): void
    {
        $this->injectPgBinToPath();

        // FIX untuk error: "ZipArchive::close(): Renaming temporary file failed"
        // Memaksa PHP CLI menggunakan storage lokal sebagai temporary directory (mencegah cross-device link issue atau /tmp penuh)
        $tempDir = storage_path('app/backup-temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        putenv('TMPDIR=' . $tempDir);

        $diskName         = config('backup.backup.destination.disks')[0];
        $backupFolderName = config('backup.backup.name');
        $disk             = Storage::disk($diskName);
        
        $countBefore = $disk->exists($backupFolderName)
            ? count(array_filter($disk->allFiles($backupFolderName), fn ($f) => str_ends_with($f, '.zip')))
            : 0;

        try {
            // 1. Eksekusi proses backup di background worker
            Log::info('Memulai pembuatan backup di background (mencegah timeout Cloudflare)...');
            Artisan::call('backup:run');
            $artisanOutput = Artisan::output();
            Log::info('backup:run output (Background)', ['output' => $artisanOutput]);

            // Verifikasi: pastikan ada file ZIP baru yang terbentuk
            $countAfter = $disk->exists($backupFolderName)
                ? count(array_filter($disk->allFiles($backupFolderName), fn ($f) => str_ends_with($f, '.zip')))
                : 0;

            if ($countAfter <= $countBefore) {
                Log::error('Backup selesai (Background) tapi tidak ada file ZIP baru. Output: '.$artisanOutput);
                return;
            }

            // 2. INJEKSI MANIFEST.JSON
            $allZipFiles = array_filter($disk->allFiles($backupFolderName), fn ($f) => str_ends_with($f, '.zip'));
            usort($allZipFiles, function ($a, $b) use ($disk) {
                return $disk->lastModified($b) <=> $disk->lastModified($a);
            });
            $latestZipFile = $allZipFiles[0];
            $latestZipPath = $disk->path($latestZipFile);

            $appVersion = 'N/A';
            $versionFile = base_path('version.json');
            if (file_exists($versionFile)) {
                $versionData = json_decode(file_get_contents($versionFile), true);
                $appVersion = $versionData['version'] ?? 'N/A';
            }

            $latestMigration = DB::table('migrations')->orderBy('migration', 'desc')->first();
            $dbMigrationVersion = $latestMigration ? $latestMigration->migration : 'N/A';

            $manifestData = [
                'app_version' => $appVersion,
                'db_migration_version' => $dbMigrationVersion,
                'created_at' => now()->toDateTimeString(),
            ];

            $zip = new ZipArchive();
            if ($zip->open($latestZipPath) === true) {
                $zip->addFromString('manifest.json', json_encode($manifestData, JSON_PRETTY_PRINT));
                $zip->close();
                Log::info("Berhasil menginjeksi manifest.json ke backup (Background): {$latestZipFile}");
            } else {
                Log::warning("Gagal menyisipkan manifest.json ke dalam backup ZIP (Background).");
            }

            ActivityLogger::log('BACKUP', "Backup database baru berhasil dibuat (Background): {$latestZipFile}");

            // 3. SETELAH SELESAI LOKAL -> DISPATCH JOB SYNC S3 (Jika tidak onlyLocal)
            if (!$this->onlyLocal) {
                Log::info("Backup lokal selesai, otomatis melempar job SyncBackupToS3Job...");
                SyncBackupToS3Job::dispatch(basename($latestZipFile));
            } else {
                Log::info("Backup lokal selesai. Opsi onlyLocal aktif, melewatinya sinkronisasi ke S3 MinIO.");
            }

        } catch (\Exception $e) {
            Log::error('CreateBackupJob Error: '.$e->getMessage());
            throw $e;
        }
    }

    private function injectPgBinToPath()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $pgBin = 'C:\Program Files\PostgreSQL\16\bin';
            $currentPath = getenv('PATH');
            
            if (strpos($currentPath, $pgBin) === false) {
                putenv("PATH={$pgBin};{$currentPath}");
            }
        }
    }
}
