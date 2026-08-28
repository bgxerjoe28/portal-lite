<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncPublicStorageToS3Command extends Command
{
    protected $signature = 'storage:sync-public-to-s3
                            {--disk=s3_local : Target disk tujuan di config/filesystems.php}
                            {--folder= : Folder spesifik di storage/app/public (contoh: settings, avatars)}
                            {--force : Timpa (overwrite) file yang sudah ada di S3}
                            {--dry-run : Jalankan simulasi tanpa mengunggah file}
                            {--delete-local : Hapus file lokal setelah berhasil diunggah}';

    protected $description = 'Sinkronkan semua file dari disk public (storage/app/public) ke disk S3 (MinIO/AWS)';

    public function handle(): int
    {
        $targetDisk = $this->option('disk') ?: 's3_local';
        $folder = $this->option('folder') ? trim($this->option('folder'), '/') : '';
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $deleteLocal = (bool) $this->option('delete-local');

        if (!array_key_exists($targetDisk, config('filesystems.disks'))) {
            $this->error("Disk target [{$targetDisk}] tidak ditemukan di config/filesystems.php!");
            return self::FAILURE;
        }

        $this->info("=== SINKRONISASI STORAGE PUBLIC KE S3 ===");
        $this->line("Sumber  : Disk [public] (storage/app/public" . ($folder ? "/{$folder}" : "") . ")");
        $this->line("Tujuan  : Disk [{$targetDisk}] (Bucket: " . config("filesystems.disks.{$targetDisk}.bucket") . ")");
        $this->line("Mode    : " . ($dryRun ? "SIMULASI (Dry Run)" : "EKSEKUSI NYATA"));
        $this->line("Paksa   : " . ($force ? "Ya (Overwrite file jika sudah ada)" : "Tidak (Lewati file yang sudah ada)"));
        $this->newLine();

        // Uji koneksi ke disk target
        try {
            Storage::disk($targetDisk)->put('.test_sync_connection', 'ok');
            Storage::disk($targetDisk)->delete('.test_sync_connection');
        } catch (\Throwable $e) {
            $this->error("Gagal terhubung ke disk target [{$targetDisk}]: " . $e->getMessage());
            return self::FAILURE;
        }

        $publicDisk = Storage::disk('public');
        $s3Disk = Storage::disk($targetDisk);

        // Preload daftar file di S3 untuk performa cepat (O(1) memory lookup)
        $existingS3Files = [];
        if (!$force) {
            $this->info("Memindai file yang sudah ada di S3...");
            try {
                $rawS3Files = $s3Disk->allFiles($folder);
                foreach ($rawS3Files as $s3File) {
                    $existingS3Files[$s3File] = true;
                }
                $this->info("-> Ditemukan " . count($existingS3Files) . " file yang sudah ada di S3.");
            } catch (\Throwable $e) {
                $this->warn("-> Gagal memuat daftar awal dari S3: " . $e->getMessage());
            }
        }

        $this->info("Memindai file lokal di disk public...");
        $allFiles = $publicDisk->allFiles($folder);

        // Filter file tersembunyi
        $files = array_values(array_filter($allFiles, function ($file) {
            return !str_starts_with(basename($file), '.');
        }));

        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $this->warn("Tidak ada file yang ditemukan untuk disinkronkan.");
            return self::SUCCESS;
        }

        $this->info("-> Ditemukan {$totalFiles} file lokal.");

        // Ringkasan per folder
        $folderSummary = [];
        foreach ($files as $file) {
            $parts = explode('/', $file);
            $rootFolder = count($parts) > 1 ? $parts[0] . '/' : '[root]';
            $folderSummary[$rootFolder] = ($folderSummary[$rootFolder] ?? 0) + 1;
        }

        $this->line("Ringkasan folder:");
        foreach ($folderSummary as $f => $count) {
            $this->line("  📁 {$f} : {$count} file");
        }
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalFiles);
        $progressBar->start();

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($files as $file) {
            try {
                $existsInS3 = isset($existingS3Files[$file]);

                if (!$force && $existsInS3) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                if ($dryRun) {
                    $uploaded++;
                    $progressBar->advance();
                    continue;
                }

                $stream = $publicDisk->readStream($file);
                if ($stream === false) {
                    throw new \RuntimeException("Gagal membaca stream file lokal: {$file}");
                }

                $writeSuccess = $s3Disk->writeStream($file, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (!$writeSuccess) {
                    throw new \RuntimeException("Gagal mengunggah file ke S3: {$file}");
                }

                $uploaded++;

                if ($deleteLocal) {
                    $publicDisk->delete($file);
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "[{$file}]: " . $e->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("==========================================");
        $this->info("Hasil Sinkronisasi:");
        $this->line("  - Total File Lokal    : {$totalFiles}");
        $this->line("  - Berhasil Diunggah   : {$uploaded}");
        $this->line("  - Dilewati (Sudah Ada): {$skipped}");
        if ($failed > 0) {
            $this->error("  - Gagal               : {$failed}");
            $this->newLine();
            $this->error("Detail error (maks 10):");
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line("  ⚠️ {$err}");
            }
        }
        $this->info("==========================================");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
