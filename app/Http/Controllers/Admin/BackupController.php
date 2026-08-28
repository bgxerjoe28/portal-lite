<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use ZipArchive;
use App\Services\ActivityLogger;

class BackupController extends Controller
{
    private function getZipErrorMessage(int $code)
    {
        switch ($code) {
            case ZipArchive::ER_EXISTS: return 'File sudah ada';
            case ZipArchive::ER_INCONS: return 'Zip tidak konsisten';
            case ZipArchive::ER_INVAL:  return 'Argumen tidak valid';
            case ZipArchive::ER_MEMORY: return 'Memory full';
            case ZipArchive::ER_NOENT:  return 'File tidak ditemukan (No such file)';
            case ZipArchive::ER_NOZIP:  return 'Bukan file ZIP yang valid';
            case ZipArchive::ER_OPEN:   return 'Gagal membuka file';
            case ZipArchive::ER_READ:   return 'Read error';
            case ZipArchive::ER_SEEK:   return 'Seek error';
            default: return 'Unknown error';
        }
    }

    public function index()
    {
        // Ambil nama disk dari config
        $diskName = config('backup.backup.destination.disks')[0];
        $disk = Storage::disk($diskName);

        // Nama folder backup (sesuai config backup.name)
        $backupFolderName = config('backup.backup.name');

        $backups = [];

        // Cek apakah direktori ada
        if ($disk->exists($backupFolderName)) {
            $files = $disk->allFiles($backupFolderName);

            foreach ($files as $file) {
                // Kita hanya ambil file .zip
                if (pathinfo($file, PATHINFO_EXTENSION) == 'zip') {
                    $backups[] = [
                        'file_name' => str_replace($backupFolderName.'/', '', $file),
                        'file_size' => $this->formatBytes($disk->size($file)),
                        'last_modified' => Carbon::createFromTimestamp($disk->lastModified($file))->setTimezone(config('app.timezone'))->translatedFormat('d F Y, H:i'),
                        'raw_last_modified' => $disk->lastModified($file),

                    ];
                }
            }
        }

        // Urutkan dari yang terbaru
        $backups = collect($backups)->sortByDesc('raw_last_modified')->values()->all();

        return Inertia::render('Admin/Backup/Index', [
            'backups' => $backups,
            'disk_usage' => $this->getDiskUsage(),
            'system_check' => $this->checkSystemRequirements(),
            'system_info' => $this->getSystemInfo(),
            's3_status' => $this->performS3Diagnostic(),
            'uvicorn_status' => $this->performUvicornDiagnostic(),
            'is_production' => app()->environment('production', 'prod'),
            'app_env' => app()->environment(),
            'db_name' => config('database.connections.pgsql.database', env('DB_DATABASE', 'portal_db')),
        ]);
    }

    public function create(Request $request)
    {
        try {
            // Validasi Password Pengaman Backup
            if (empty(config('backup.backup.password'))) {
                return back()->with('error', 'Konfigurasi password backup belum diatur. Silakan atur variabel BACKUP_ARCHIVE_PASSWORD di file .env terlebih dahulu.');
            }

            $onlyLocal = $request->boolean('only_local', false);

            if (app()->environment('lokal', 'local')) {
                set_time_limit(600);
                \App\Jobs\CreateBackupJob::dispatchSync($onlyLocal);
                $message = $onlyLocal
                    ? 'Backup lokal berhasil dibuat secara langsung!'
                    : 'Backup berhasil dibuat dan diproses!';
            } else {
                \App\Jobs\CreateBackupJob::dispatch($onlyLocal);
                $message = $onlyLocal
                    ? 'Perintah pembuatan backup lokal (tanpa S3 MinIO) sedang diproses di latar belakang! Silakan cek halaman ini beberapa saat lagi.'
                    : 'Perintah pembuatan backup sedang diproses di latar belakang! Jika lokal sudah selesai, ia akan otomatis dikirim ke S3 MinIO. Silakan cek halaman ini beberapa saat lagi.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Backup Queue Error: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses antrean backup: '.$e->getMessage());
        }
    }

    public function download(string $file_name)
    {
        // FIX: sanitasi nama file agar tidak bisa keluar dari folder backup (path traversal)
        $fileName = basename($file_name);
        $backupFolderName = config('backup.backup.name');
        $path             = $backupFolderName . '/' . $fileName;

        // SMART DOWNLOAD LOGIC:
        // Cek apakah file ada di S3/MinIO terlebih dahulu.
        // Jika ada, gunakan Presigned URL untuk bypass Cloudflare (mencegah timeout 524).
        $targetS3Disk = 's3_local';
        $s3Disk = Storage::disk($targetS3Disk);
        $s3Config = config("filesystems.disks.{$targetS3Disk}", []);
        $s3Configured = !empty($s3Config['key'] ?? null);

        if ($s3Configured && $s3Disk->exists($path)) {
            try {
                if (method_exists($s3Disk, 'temporaryUrl')) {
                    $temporaryUrl = $s3Disk->temporaryUrl($path, now()->addMinutes(15));
                    ActivityLogger::log('BACKUP', "Download backup via Presigned URL S3: {$fileName}");
                    return redirect()->away($temporaryUrl);
                }
            } catch (\Throwable $e) {
                Log::warning('Presigned URL gagal di-generate dari S3, fallback ke stream lokal.', ['error' => $e->getMessage()]);
            }
        }

        // FALLBACK: Cek di disk utama (lokal)
        $diskName = config('backup.backup.destination.disks')[0];
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        if (! $disk->exists($path)) {
            return back()->with('error', 'File tidak ditemukan di server lokal maupun S3.');
        }

        // Stream langsung dari lokal (melewati Cloudflare, ada risiko timeout untuk file besar)
        ActivityLogger::log('BACKUP', "Download backup via lokal streaming: {$fileName}");
        return $disk->download($path);
    }

    public function destroy(string $file_name)
    {
        // FIX: sanitasi nama file — mencegah penghapusan file arbitrary di luar folder backup
        $fileName = basename($file_name);

        $diskName = config('backup.backup.destination.disks')[0];
        $backupFolderName = config('backup.backup.name');
        $path = $backupFolderName.'/'.$fileName;

        if (Storage::disk($diskName)->exists($path)) {
            Storage::disk($diskName)->delete($path);

            ActivityLogger::log('BACKUP', "File backup dihapus: {$fileName}");

            return back()->with('success', 'File backup berhasil dihapus.');
        }

        return back()->with('error', 'File gagal dihapus.');
    }

    public function syncToS3(string $file_name)
    {
        try {
            $fileName = basename($file_name);
            $backupFolderName = config('backup.backup.name');
            $path = $backupFolderName . '/' . $fileName;

            // Pastikan file ada di disk lokal
            if (!Storage::disk('local')->exists($path)) {
                return back()->with('error', 'File backup tidak ditemukan di server lokal.');
            }

            // Dispatch job ke background
            \App\Jobs\SyncBackupToS3Job::dispatch($fileName);

            return back()->with('success', 'Proses sinkronisasi ke S3 Minio sedang berjalan di latar belakang. Silakan cek tabel antrean atau notifikasi log.');

        } catch (\Exception $e) {
            Log::error('S3 Sync Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses antrean S3: ' . $e->getMessage());
        }
    }

    // --- HELPER METHODS ---

    private function getSystemInfo(): array
    {
        // Versi OS
        $osVersion = PHP_OS_FAMILY === 'Windows'
            ? 'Windows '
            : '';
        $osVersion .= php_uname('s').' '.php_uname('r');

        // Versi Database (PostgreSQL)
        try {
            $dbVersion = DB::selectOne('SELECT version() as ver')->ver;
            // Ambil hanya bagian singkat, misal "PostgreSQL 15.3"
            preg_match('/PostgreSQL [\d.]+/', $dbVersion, $match);
            $dbVersion = $match[0] ?? $dbVersion;
        } catch (\Throwable $e) {
            $dbVersion = 'Tidak dapat dibaca';
        }

        // Versi Aplikasi dari version.json
        $versionFile = base_path('version.json');
        $appVersion = 'N/A';
        if (file_exists($versionFile)) {
            $versionData = json_decode(file_get_contents($versionFile), true);
            $appVersion = $versionData['version'] ?? 'N/A';
        }

        return [
            'os'      => $osVersion,
            'db'      => $dbVersion,
            'php'     => PHP_VERSION,
            'laravel' => app()->version(),
            'app'     => $appVersion,
        ];
    }

    private function formatBytes(int|float $bytes, int $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision).' '.$units[$pow];
    }

    private function getDiskUsage()
    {
        // Sederhana: Menghitung free space di server (Linux)
        return [
            'free' => $this->formatBytes(disk_free_space(storage_path())),
            'total' => $this->formatBytes(disk_total_space(storage_path())),
        ];
    }

    public function restore(Request $request, string $file_name)
    {
        // FIX: sanitasi nama file agar tidak bisa keluar dari folder backup (path traversal)
        $fileName = basename($file_name);

        $diskName = config('backup.backup.destination.disks')[0];
        $backupFolderName = config('backup.backup.name');
        // FIX: konsisten pakai '/' (bukan DIRECTORY_SEPARATOR) untuk path Storage disk
        $relativePath = $backupFolderName.'/'.$fileName;

        if (! Storage::disk($diskName)->exists($relativePath)) {
            return back()->with('error', 'File tidak ditemukan di storage: '.$relativePath);
        }

        $tempCreated = false;
        try {
            $sourcePath = Storage::disk($diskName)->path($relativePath);
        } catch (\Throwable $e) {
            // Untuk remote disk (S3/MinIO) yang tidak mendukung local path:
            $tempDir = storage_path('app/backup-temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $sourcePath = $tempDir.'/restore-temp-'.$fileName;
            file_put_contents($sourcePath, Storage::disk($diskName)->get($relativePath));
            $tempCreated = true;
        }

        try {
            return $this->restoreFromZipPath($sourcePath, $request);
        } finally {
            if ($tempCreated && file_exists($sourcePath)) {
                @unlink($sourcePath);
            }
        }
    }

    /**
     * Inti logika restore: menerima absolute path file ZIP dan menjalankan seluruh proses restore.
     */
    private function restoreFromZipPath(string $sourcePath, ?Request $request = null)
    {
        $dbConfig = config('database.connections.pgsql');

        // Injeksikan direktori bin PostgreSQL ke PATH agar psql dapat dieksekusi
        // hanya dengan nama pendek tanpa hard path, kompatibel lintas versi/instalasi.
        $this->injectPgBinToPath();

        $tempPath = storage_path('app'.DIRECTORY_SEPARATOR.'backup-temp'.DIRECTORY_SEPARATOR.'restore-'.time());
        $safetyDumpPath = null;

        try {
            // Hindari timeout eksekusi PHP pada proses I/O besar
            @set_time_limit(0);
            if (function_exists('ini_set')) {
                @ini_set('max_execution_time', '0');
            }
            Artisan::call('down');

            // 1. Buat folder temp
            File::makeDirectory($tempPath, 0755, true, true);

            // 2. Buka & Ekstrak ZIP
            $zip = new ZipArchive;
            $res = $zip->open($sourcePath);

            if ($res !== true) {
                throw new \Exception('Gagal membuka ZIP. Kode: '.$res.' ('.$this->getZipErrorMessage($res).')');
            }

            $password = config('backup.backup.password');
            if ($password) {
                $zip->setPassword($password);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                // Reset time limit berkala agar worker tidak terkena timeout
                if ($i % 200 === 0) {
                    @set_time_limit(0);
                }

                $filename = $zip->getNameIndex($i);

                // Skip directory entries (berakhiran '/') — tidak perlu di-fopen
                if (str_ends_with($filename, '/')) {
                    continue;
                }

                // Ganti karakter terlarang Windows dengan underscore, tapi SIMPAN ekstensi aslinya
                if (str_ends_with(strtolower($filename), '.sql.gz')) {
                    $ext  = '.sql.gz';
                    $base = substr($filename, 0, -strlen('.sql.gz'));
                } elseif (str_ends_with(strtolower($filename), '.sql')) {
                    $ext  = '.sql';
                    $base = substr($filename, 0, -strlen('.sql'));
                } else {
                    $ext  = '';
                    $base = $filename;
                }
                $safeName = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $base).$ext;

                $osPath   = str_replace('/', DIRECTORY_SEPARATOR, $safeName);
                $fullPath = $tempPath.DIRECTORY_SEPARATOR.$osPath;
                $dir      = dirname($fullPath);

                if (! is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                $fp = $zip->getStream($filename);
                if ($fp) {
                    $outFp = fopen($fullPath, 'wb');
                    if ($outFp) {
                        while (! feof($fp)) {
                            fwrite($outFp, fread($fp, 8192));
                        }
                        fclose($outFp);
                    }
                    fclose($fp);
                } else {
                    // Karena direktori sudah di-skip di atas, jika file gagal diekstrak (getStream = false),
                    // penyebab utamanya hampir pasti adalah PASSWORD SALAH atau file ZIP rusak.
                    throw new \Exception("Gagal mengekstrak isi backup ({$filename}). Kemungkinan password BACKUP_ARCHIVE_PASSWORD di .env salah (berbeda dengan password saat backup ini dibuat).");
                }
            }
            $zip->close();

            @set_time_limit(0);

            // 3. Cari file SQL
            $allFiles = File::allFiles($tempPath);
            Log::info('Isi folder ekstraksi backup:', [
                'count' => count($allFiles),
                'files' => collect($allFiles)->map(fn ($f) => $f->getRelativePathname())->toArray(),
            ]);

            $backupFile = null;
            foreach ($allFiles as $file) {
                $lower = strtolower($file->getFilename());
                if (str_ends_with($lower, '.sql.gz') || str_ends_with($lower, '.sql')) {
                    $backupFile = $file->getPathname();
                    break;
                }
            }

            if (! $backupFile) {
                throw new \Exception('File dump database (.sql / .sql.gz) tidak ditemukan di dalam ZIP. Files ditemukan: '.implode(', ', collect($allFiles)->map(fn ($f) => $f->getFilename())->toArray()));
            }

            // --- VALIDASI MANIFEST.JSON (VERSI DATABASE) ---
            $manifestFile = null;
            foreach ($allFiles as $file) {
                if (strtolower($file->getFilename()) === 'manifest.json') {
                    $manifestFile = $file->getPathname();
                    break;
                }
            }

            $challengeInput = $request
                ? trim((string) $request->input('challenge_keyword', $request->input('challenge', $request->input('force', ''))))
                : trim((string) request('challenge_keyword', request('challenge', request('force', ''))));
            $validChallenges = ['FORCE_RESTORE', 'FORCE', 'RESTORE_FORCE'];
            $isForceRestore = in_array(strtoupper($challengeInput), $validChallenges, true);

            if (! $manifestFile) {
                if (! $isForceRestore) {
                    throw new \Exception('Backup DITOLAK: File manifest.json (penanda versi) tidak ditemukan. Ini adalah backup versi lama (Legacy). Untuk memaksakan restore (Force Restore), masukkan keyword challenge: FORCE_RESTORE');
                }
                Log::warning('FORCE RESTORE dijalankan dengan keyword challenge pada backup tanpa manifest.json (Legacy).');
            } else {
                $manifestData = json_decode(file_get_contents($manifestFile), true);
                $latestMigration = DB::table('migrations')->orderBy('migration', 'desc')->first();
                $currentDbVersion = $latestMigration ? $latestMigration->migration : 'N/A';
                $backupDbVersion = $manifestData['db_migration_version'] ?? 'N/A';

                if ($backupDbVersion !== $currentDbVersion) {
                    if (! $isForceRestore) {
                        throw new \Exception('Backup DITOLAK: Versi database pada backup ('.($backupDbVersion ?: 'Unknown').') tidak sesuai dengan versi database server saat ini ('.$currentDbVersion.'). Untuk memaksakan restore (Force Restore), masukkan keyword challenge: FORCE_RESTORE');
                    }
                    Log::warning("FORCE RESTORE diizinkan dengan keyword challenge [{$challengeInput}]. Versi backup ({$backupDbVersion}) berbeda dari server ({$currentDbVersion}).");
                } else {
                    Log::info('Validasi manifest.json berhasil', ['backup_db_version' => $backupDbVersion]);
                }
            }
            // -----------------------------------------------

            // 4. Dekompresi .gz jika perlu
            $sqlFilePath = $backupFile;
            if (str_ends_with(strtolower($backupFile), '.gz')) {
                $sqlFilePath = preg_replace('/\.gz$/i', '', $backupFile);
                $this->unzipGzip($backupFile, $sqlFilePath);
            }

            @set_time_limit(0);

            // --- FIX: SANITASI FILE SQL SEBELUM RESTORE ---
            // Bersihkan perintah SET ROLE / OWNER TO / GRANT agar kompatibel dengan user DB non-superuser
            $this->sanitizeSqlDumpFile($sqlFilePath);

            // 5. Jalankan psql — cukup panggil nama binary pendek karena direktori bin
            //    sudah diinjeksikan ke PATH di awal method ini.
            //    Di Windows, gunakan 'psql' (tanpa .exe) agar tetap konsisten.
            $psqlBin = 'psql';

            // FIX: gunakan escapeshellarg() untuk setiap argumen guna mencegah command injection
            // (nama file dari dalam ZIP hanya disaring dari karakter Windows, bukan shell metachar).
            $command = sprintf(
                '%s -v ON_ERROR_STOP=1 -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellcmd($psqlBin),
                escapeshellarg($dbConfig['host']),
                escapeshellarg((string) ($dbConfig['port'] ?? '5432')),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($sqlFilePath)
            );

            // --- FIX: SAFETY DUMP SEBELUM db:wipe ---
            // Sebelumnya db:wipe langsung dijalankan sebelum tahu restore akan berhasil.
            // Kalau psql gagal di tengah jalan, database sudah kosong tanpa jalan kembali.
            // Sekarang: buat dump dari kondisi saat ini dulu, baru wipe + restore.
            // Kalau restore gagal, rollback otomatis dari safety dump ini.
            $safetyDumpPath = storage_path('app'.DIRECTORY_SEPARATOR.'backup-temp'.DIRECTORY_SEPARATOR.'pre-restore-safety-'.time().'.sql');

            $safetyDumpCommand = sprintf(
                'pg_dump --no-owner --no-privileges -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellarg($dbConfig['host']),
                escapeshellarg((string) ($dbConfig['port'] ?? '5432')),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($safetyDumpPath)
            );

            putenv("PGPASSWORD={$dbConfig['password']}");
            exec($safetyDumpCommand, $safetyOutput, $safetyReturnVar);
            putenv('PGPASSWORD');

            if ($safetyReturnVar !== 0) {
                throw new \Exception('Gagal membuat safety dump sebelum restore. Restore dibatalkan demi keamanan data. Output: '.implode(' | ', array_slice($safetyOutput, -5)));
            }

            $this->sanitizeSqlDumpFile($safetyDumpPath);

            Log::info('Safety dump sebelum restore berhasil dibuat', ['path' => $safetyDumpPath]);

            // KOSONGKAN DATABASE DULU SEBELUM RESTORE
            // Ini untuk mencegah error "constraint already exists" atau "relation already exists"
            // karena Spatie backup mengekspor CREATE TABLE tanpa DROP TABLE.
            Log::info('Mengosongkan database saat ini (db:wipe)...');
            Artisan::call('db:wipe', ['--force' => true]);

            Log::info('Menjalankan restore psql', ['command' => $command]);
            putenv("PGPASSWORD={$dbConfig['password']}");
            exec($command, $output, $returnVar);
            putenv('PGPASSWORD');

            Log::info('Hasil psql restore', [
                'return_var' => $returnVar,
                'output_tail' => array_slice($output, -20),
            ]);

            if ($returnVar !== 0) {
                // FIX: ROLLBACK OTOMATIS dari safety dump jika restore gagal di tengah jalan
                Log::error('Restore psql gagal, mencoba rollback dari safety dump...');

                Artisan::call('db:wipe', ['--force' => true]);

                $rollbackCommand = sprintf(
                    'psql -v ON_ERROR_STOP=1 -h %s -p %s -U %s -d %s -f %s 2>&1',
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg((string) ($dbConfig['port'] ?? '5432')),
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['database']),
                    escapeshellarg($safetyDumpPath)
                );

                putenv("PGPASSWORD={$dbConfig['password']}");
                exec($rollbackCommand, $rollbackOutput, $rollbackReturnVar);
                putenv('PGPASSWORD');

                if ($rollbackReturnVar === 0) {
                    throw new \Exception('Restore gagal, tapi database berhasil dikembalikan ke kondisi sebelum restore (rollback otomatis berhasil). Exit code restore: '.$returnVar.'. Output: '.implode(' | ', array_slice($output, -5)));
                }

                throw new \Exception('KRITIS: Restore gagal DAN rollback otomatis JUGA gagal. Database mungkin dalam keadaan kosong/tidak lengkap! Safety dump masih tersimpan manual di: '.$safetyDumpPath.'. Output restore: '.implode(' | ', array_slice($output, -5)));
            }

            // Restore sukses — safety dump tidak diperlukan lagi
            if ($safetyDumpPath && file_exists($safetyDumpPath)) {
                @unlink($safetyDumpPath);
            }

            @set_time_limit(0);

            // 6. Pulihkan file fisik
            $allDirectories = File::allDirectories($tempPath);
            foreach ($allDirectories as $dir) {
                $normalizedDir = str_replace('\\', '/', $dir);

                if (str_ends_with($normalizedDir, 'storage/app/public')) {
                    File::copyDirectory($dir, storage_path('app'.DIRECTORY_SEPARATOR.'public'));
                }
                if (str_ends_with($normalizedDir, 'public/storage')) {
                    File::copyDirectory($dir, storage_path('app'.DIRECTORY_SEPARATOR.'public'));
                }
                // Tangkap semua subfolder di bawah public/uploads (mis. uploads/cbt_questions)
                if (preg_match('#public/uploads(/.*)?$#', $normalizedDir)) {
                    $destSuffix = preg_replace('#^.*public/uploads#', '', $normalizedDir);
                    $dest       = public_path('uploads'.$destSuffix);
                    if (! is_dir($dest)) {
                        mkdir($dest, 0777, true);
                    }
                    File::copyDirectory($dir, $dest);
                }
            }

            @set_time_limit(0);

            // 7. Ambil statistik pasca-restore untuk notifikasi
            $stats = $this->getRestoreStats();

            // 8. Bersih-bersih (Gunakan fast delete OS native agar tidak timeout di ribuan file)
            $this->fastDeleteDirectory($tempPath);
            Artisan::call('cache:clear');
            Artisan::call('up');

            ActivityLogger::log('RESTORE', "Database berhasil direstore dari backup. {$stats}");

            return back()->with('success', 'Restore berhasil! '.$stats);

        } catch (\Exception $e) {
            if (File::isDirectory($tempPath)) {
                $this->fastDeleteDirectory($tempPath);
            }
            Artisan::call('up');
            Log::error('Restore Error: '.$e->getMessage());

            return back()->with('error', 'Gagal melakukan restore: '.$e->getMessage());
        }
    }

    /**
     * Ambil statistik ringkas dari database setelah restore untuk ditampilkan di notifikasi.
     */
    private function getRestoreStats(): string
    {
        try {
            $parts = [];

            if (DB::getSchemaBuilder()->hasTable('users')) {
                $total = DB::table('users')->count();
                $parts[] = "{$total} akun user";
            }
            if (DB::getSchemaBuilder()->hasTable('students')) {
                $total = DB::table('students')->count();
                $parts[] = "{$total} data siswa";
            }
            if (DB::getSchemaBuilder()->hasTable('teachers')) {
                $total = DB::table('teachers')->count();
                $parts[] = "{$total} data guru";
            }
            if (DB::getSchemaBuilder()->hasTable('cbt_questions')) {
                $total = DB::table('cbt_questions')->count();
                $parts[] = "{$total} soal CBT";
            }

            return empty($parts) ? 'Database berhasil dipulihkan.' : 'Database berhasil dipulihkan. Terdeteksi: '.implode(', ', $parts).'.';
        } catch (\Throwable $e) {
            Log::warning('getRestoreStats error: '.$e->getMessage());
            return 'Database berhasil dipulihkan.';
        }
    }

    // Helper untuk bongkar Gzip
    private function unzipGzip(string $source, string $dest)
    {
        $s = gzopen($source, 'rb');
        $d = fopen($dest, 'wb');
        while ($string = gzread($s, 4096)) {
            fwrite($d, $string, strlen($string));
        }
        gzclose($s);
        fclose($d);
    }

    /**
     * Bersihkan file SQL dump dari perintah kepemilikan (OWNER TO), perubahan role (SET ROLE / SET SESSION AUTHORIZATION),
     * serta perintah GRANT/REVOKE yang dapat menyebabkan error saat di-restore oleh database user non-superuser di server staging/production.
     */
    private function sanitizeSqlDumpFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $tempSanitizedPath = $filePath.'.sanitized';
        $in = @fopen($filePath, 'rb');
        $out = @fopen($tempSanitizedPath, 'wb');

        if (! $in || ! $out) {
            if ($in) {
                fclose($in);
            }
            if ($out) {
                fclose($out);
            }

            return;
        }

        while (($line = fgets($in)) !== false) {
            $trimmed = trim($line);

            // Lewati atau jadikan komentar baris yang mencoba mengubah peran (role), kepemilikan (owner), atau hak akses (privileges)
            if (
                preg_match('/^SET\s+(ROLE|SESSION\s+AUTHORIZATION)\s+/i', $trimmed) ||
                preg_match('/^ALTER\s+.*?\s+OWNER\s+TO\s+/i', $trimmed) ||
                preg_match('/^(GRANT|REVOKE)\s+/i', $trimmed) ||
                preg_match('/^ALTER\s+DEFAULT\s+PRIVILEGES\s+/i', $trimmed)
            ) {
                fwrite($out, "-- [SANITIZED BY PORTAL SMA] ".$line);
                continue;
            }

            fwrite($out, $line);
        }

        fclose($in);
        fclose($out);

        // Gantikan file asal dengan file yang sudah bersih
        @unlink($filePath);
        rename($tempSanitizedPath, $filePath);

        Log::info('Berhasil membersihkan file SQL dump dari perintah ROLE/OWNER/PRIVILEGES', ['path' => $filePath]);
    }

    /**
     * Hapus direktori sementara secara instan menggunakan perintah native OS
     * untuk mencegah timeout eksekusi PHP pada direktori berisi ribuan file.
     */
    private function fastDeleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Di Windows: rmdir /s /q sangat cepat & tidak memicu PHP loop per-file
                $escaped = escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $path));
                exec("rmdir /s /q {$escaped} 2>nul");
            } else {
                // Di Linux/Unix: rm -rf
                $escaped = escapeshellarg($path);
                exec("rm -rf {$escaped} 2>/dev/null");
            }
        } catch (\Throwable $e) {
            Log::warning('fastDeleteDirectory native command error: '.$e->getMessage());
        }

        // Fallback jika direktori masih ada
        if (is_dir($path)) {
            try {
                File::deleteDirectory($path);
            } catch (\Throwable $e) {
                Log::warning('Fallback deleteDirectory failed: '.$e->getMessage());
            }
        }
    }

    // Helper bersih-bersih
    private function cleanupRestore(string $path)
    {
        if (is_dir($path)) {
            array_map('unlink', glob("$path/db-dumps/*.*"));
            rmdir("$path/db-dumps");
            rmdir($path);
        }
    }

    private function checkSystemRequirements()
    {
        // Gunakan hanya nama pendek binary; ketersediaan dicek via PATH (where/which).
        // Ini memastikan status yang akurat tanpa bergantung pada hard path.
        $results = [
            'pg_dump' => [
                'path' => 'pg_dump (via PATH)',
                'installed' => $this->checkViaCommand('pg_dump'),
                'label' => 'Backup Engine (pg_dump)',
            ],
            'psql' => [
                'path' => 'psql (via PATH)',
                'installed' => $this->checkViaCommand('psql'),
                'label' => 'Restore Engine (psql)',
            ],
            'storage' => [
                'path' => storage_path('app/backup-temp'),
                'installed' => is_writable(storage_path('app')),
                'label' => 'Izin Tulis Folder Storage',
            ],
        ];

        // Status keseluruhan
        $isReady = $results['pg_dump']['installed'] && $results['psql']['installed'] && $results['storage']['installed'];

        return [
            'is_ready' => $isReady,
            'details' => $results,
        ];
    }

    public function restoreFromUpload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|mimes:zip|max:512000', // Max 500MB
        ]);

        try {
            $file = $request->file('backup_file');
            $fileName = 'upload-restore-'.time().'.zip';

            // Simpan langsung ke disk backup agar bisa diproses oleh restoreFromZipPath
            $diskName = config('backup.backup.destination.disks')[0];
            $backupFolderName = config('backup.backup.name');
            $relativePath = $backupFolderName.'/'.$fileName;

            Storage::disk($diskName)->put($relativePath, file_get_contents($file->getRealPath()));

            $response = $this->restoreFromZipPath($file->getRealPath(), $request);

            // Hapus file upload sementara dari storage disk setelah restore
            Storage::disk($diskName)->delete($relativePath);

            return $response;

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload & restore: '.$e->getMessage());
        }
    }

    /**
     * Resolve direktori bin PostgreSQL:
     * 1. Coba dari config database.connections.pgsql.dump.dump_binary_path
     * 2. Jika tidak ada / tidak valid, resolve dari PATH via 'where' (Windows) atau 'which' (Unix)
     * 3. Return null jika tidak ditemukan (binary sudah ada di PATH, tidak perlu inject)
     */
    private function resolvePgBinDir(): ?string
    {
        // Cek dari config terlebih dahulu
        $configPath = config('database.connections.pgsql.dump.dump_binary_path');
        if ($configPath && is_dir($configPath)) {
            return $configPath;
        }

        // Fallback: temukan lokasi pg_dump via PATH
        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where pg_dump 2>nul' : 'which pg_dump 2>/dev/null';
        exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && ! empty($output[0])) {
            return dirname(trim($output[0]));
        }

        return null;
    }

    /**
     * Injeksikan direktori bin PostgreSQL ke environment PATH proses ini.
     * Diperlukan di Windows agar libpq.dll dan DLL dependensi lainnya dapat dimuat.
     * Di Unix, biasanya pg_dump/psql sudah ada di PATH — inject tetap aman.
     */
    private function injectPgBinToPath(): void
    {
        $binDir = $this->resolvePgBinDir();

        if (! $binDir) {
            // Binary sudah dapat diakses via PATH yang ada, tidak perlu inject
            return;
        }

        $currentPath = getenv('PATH') ?: '';
        $separator   = PHP_OS_FAMILY === 'Windows' ? ';' : ':';

        if (! str_contains($currentPath, $binDir)) {
            putenv("PATH={$binDir}{$separator}{$currentPath}");
            Log::info('PostgreSQL bin dir diinjeksikan ke PATH', ['dir' => $binDir]);
        }
    }

    /**
     * Cek apakah sebuah binary tersedia via PATH (tanpa hard path).
     * Di Windows menggunakan 'where', di Unix menggunakan 'command -v'.
     */
    private function checkViaCommand(string $binary): bool
    {
        // Inject PATH terlebih dahulu agar hasil cek konsisten dengan saat runtime
        $this->injectPgBinToPath();

        $command = PHP_OS_FAMILY === 'Windows'
            ? "where {$binary} 2>nul"
            : "command -v {$binary} 2>/dev/null";

        exec($command, $output, $returnVar);

        return $returnVar === 0;
    }

    /**
     * Endpoint API untuk mengecek koneksi S3/MinIO secara real-time via AJAX.
     */
    public function checkS3Connection()
    {
        return response()->json([
            'cbt' => $this->performS3Diagnostic('s3_cbt', 'CBT Storage'),
            'local' => $this->performS3Diagnostic('s3_local', 'Local Storage')
        ]);
    }

    /**
     * Pengecekan mendalam status & kesehatan koneksi MinIO / S3 Object Storage.
     */
    private function performS3Diagnostic(string $diskName = 's3_local', string $label = 'MinIO'): array
    {
        $filesystemDisk = config('filesystems.default', env('FILESYSTEM_DISK', 'local'));
        $backupDisks = config('backup.backup.destination.disks', ['local']);

        $diskConfig = config("filesystems.disks.{$diskName}", []);
        $accessKey = $diskConfig['key'] ?? null;
        $secretKey = $diskConfig['secret'] ?? null;
        $bucket    = $diskConfig['bucket'] ?? null;
        $endpoint  = $diskConfig['endpoint'] ?? null;
        $region    = $diskConfig['region'] ?? null;
        $pathStyle = $diskConfig['use_path_style_endpoint'] ?? false;

        $configChecks = [
            'access_key' => [
                'key' => "Access Key",
                'label' => "{$label} Access Key",
                'value' => $accessKey ? (strlen($accessKey) > 4 ? substr($accessKey, 0, 4) . '***' : '***') : 'Belum diisi',
                'status' => !empty($accessKey),
                'message' => !empty($accessKey) ? 'Access key terpasang' : 'Access key belum diatur di .env',
            ],
            'secret_key' => [
                'key' => "Secret Key",
                'label' => "{$label} Secret Key",
                'value' => $secretKey ? '********' : 'Belum diisi',
                'status' => !empty($secretKey),
                'message' => !empty($secretKey) ? 'Secret key terpasang' : 'Secret key belum diatur di .env',
            ],
            'bucket' => [
                'key' => "Bucket",
                'label' => "Bucket Name",
                'value' => $bucket ?: 'Belum diisi',
                'status' => !empty($bucket),
                'message' => !empty($bucket) ? "Bucket: {$bucket}" : 'Bucket belum diatur di .env',
            ],
            'endpoint' => [
                'key' => "Endpoint",
                'label' => "Endpoint URL",
                'value' => $endpoint ?: 'AWS Default',
                'status' => true,
                'message' => !empty($endpoint) ? "Endpoint: {$endpoint}" : 'Endpoint default',
            ],
            'path_style' => [
                'key' => "Path Style",
                'label' => "Path Style Endpoint",
                'value' => $pathStyle ? 'true' : 'false',
                'status' => (bool)$pathStyle || $diskName === 's3_cbt', // CBT doesn't strict require path style
                'message' => $pathStyle ? 'Path style aktif (Cocok untuk MinIO)' : 'False (Biasa untuk IDCloudHost)',
            ],
        ];

        // Jalankan tes operasi Tulis -> Baca -> Hapus pada disk S3
        $storageTest = [
            'success' => false,
            'response_time_ms' => 0,
            'message' => '',
            'error_details' => null,
        ];

        $startTime = microtime(true);

        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $s3Disk */
            $s3Disk = Storage::disk($diskName);
            $testFileName = "health_check_{$diskName}_" . time() . '.txt';
            $testContent = "{$label} Health Check OK at " . now()->toDateTimeString();

            // 1. Write Test
            $s3Disk->put($testFileName, $testContent);

            // 2. Read & Exists Test
            $exists = $s3Disk->exists($testFileName);
            $readContent = $exists ? $s3Disk->get($testFileName) : '';

            // 3. Delete Test
            if ($exists) {
                $s3Disk->delete($testFileName);
            }

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if ($exists && $readContent === $testContent) {
                $storageTest['success'] = true;
                $storageTest['response_time_ms'] = $responseTime;
                $storageTest['message'] = "Koneksi {$label} Berhasil! Operasi Write, Read, dan Delete sukses dalam {$responseTime} ms.";
            } else {
                $storageTest['success'] = false;
                $storageTest['message'] = "Operasi file ke {$label} tidak mengembalikan konten yang sesuai.";
            }

        } catch (\Throwable $e) {
            $endTime = microtime(true);
            $storageTest['success'] = false;
            $storageTest['response_time_ms'] = round(($endTime - $startTime) * 1000, 2);
            $storageTest['message'] = "Gagal terhubung ke {$label}.";
            $storageTest['error_details'] = $e->getMessage();
        }

        $allConfigOk = !empty($accessKey) && !empty($secretKey) && !empty($bucket);

        return [
            'is_s3_active' => $storageTest['success'],
            'all_config_ok' => $allConfigOk,
            'config_checks' => $configChecks,
            'storage_test' => $storageTest,
            'last_checked_at' => now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->translatedFormat('d F Y, H:i:s'),
        ];
    }

    private function performUvicornDiagnostic(): array
    {
        $url = config('cbt.irt_microservice_url', 'http://127.0.0.1:8085/api/v1/irt/estimate');
        $secret = config('cbt.internal_secret', 'cbt-secret-key');

        $pingUrl = str_replace('/api/v1/irt/estimate', '/', $url);

        $startTime = microtime(true);
        $isOnline = false;
        $responseTimeMs = 0;
        $errorMessage = null;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get($pingUrl);
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000, 2);

            if ($response->successful() || $response->status() === 200 || $response->status() === 404) {
                $isOnline = true;
            } else {
                $errorMessage = "HTTP Status Code: " . $response->status();
            }
        } catch (\Throwable $e) {
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000, 2);
            $errorMessage = $e->getMessage();
        }

        return [
            'url' => $url,
            'ping_url' => $pingUrl,
            'is_online' => $isOnline,
            'response_time_ms' => $responseTimeMs,
            'secret_configured' => !empty($secret),
            'secret_masked' => $secret ? (strlen($secret) > 4 ? substr($secret, 0, 4) . '***' : '***') : 'Belum diisi',
            'error_message' => $errorMessage,
            'last_checked_at' => now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->translatedFormat('d F Y, H:i:s'),
        ];
    }
}