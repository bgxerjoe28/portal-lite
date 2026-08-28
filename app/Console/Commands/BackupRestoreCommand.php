<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore
                            {file? : Nama file ZIP backup atau path lengkap file ZIP yang akan direstore}
                            {--disk= : Disk tempat file backup disimpan (default: dari config backup)}
                            {--bucket= : Nama bucket MinIO/S3 jika berbeda dari konfigurasi default}
                            {--folder= : Nama folder di dalam disk tempat file backup disimpan}
                            {--password= : Password untuk membuka file ZIP backup jika berbeda dari .env}
                            {--force : Lewati verifikasi perbedaan versi migrasi database manifest}
                            {--skip-files : Hanya restore database tanpa menimpa file storage/uploads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database dan file storage dari arsip backup ZIP (Lokal & S3 Remote)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('======================================================');
        $this->info('           PORTAL SMA - RESTORE SYSTEM BACKUP         ');
        $this->info('======================================================');

        // 1. Validasi tool PostgreSQL
        $this->injectPgBinToPath();
        if (! $this->checkSystemRequirements()) {
            return self::FAILURE;
        }

        // 2. Tentukan file backup
        $diskName = $this->option('disk') ?: config('backup.backup.destination.disks')[0];

        // Override nama bucket jika diberikan opsi --bucket
        if ($bucketOption = $this->option('bucket')) {
            config(["filesystems.disks.{$diskName}.bucket" => $bucketOption]);
            Storage::purge($diskName);
        }

        $currentBucket = config("filesystems.disks.{$diskName}.bucket");
        $backupFolderName = $this->option('folder') ?: config('backup.backup.name', env('APP_NAME', 'SMAN16_Semarang_Portal'));
        $targetFilePath = null;
        $tempCreated = false;

        if ($currentBucket) {
            $this->line("Storage Disk  : <comment>{$diskName}</comment> (Bucket: <info>{$currentBucket}</info>)");
        } else {
            $this->line("Storage Disk  : <comment>{$diskName}</comment>");
        }

        $fileArg = $this->argument('file');

        if ($fileArg) {
            // Cek apakah fileArg adalah path lokal langsung
            if (file_exists($fileArg)) {
                $targetFilePath = realpath($fileArg);
                $this->info("Menggunakan file lokal: {$targetFilePath}");
            } else {
                // Cek di disk storage
                $disk = Storage::disk($diskName);
                $fileName = basename($fileArg);
                $relativePath = null;

                $candidates = array_unique(array_filter([
                    $fileArg,
                    $backupFolderName ? ($backupFolderName . '/' . $fileName) : null,
                    $fileName,
                ]));

                foreach ($candidates as $cand) {
                    try {
                        if ($disk->exists($cand)) {
                            $relativePath = $cand;
                            break;
                        }
                    } catch (\Throwable $e) {
                        // ignore and try next
                    }
                }

                if (! $relativePath) {
                    // Fallback search via allFiles
                    try {
                        $all = array_filter($disk->allFiles($backupFolderName ?: ''), fn ($f) => basename($f) === $fileName || $f === $fileArg);
                        if (! empty($all)) {
                            $relativePath = reset($all);
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                if (! $relativePath) {
                    $this->error("File backup [{$fileArg}] tidak ditemukan di disk [{$diskName}].");
                    return self::FAILURE;
                }

                $targetFilePath = $this->resolveStorageFilePath($diskName, $relativePath, $tempCreated);
            }
        } else {
            // Jika file tidak ditentukan, tampilkan daftar pilihan dari disk
            $disk = Storage::disk($diskName);
            $allFiles = [];

            // 1. Coba baca folder spesifik jika ditentukan
            if (! empty($backupFolderName)) {
                try {
                    $found = array_filter($disk->allFiles($backupFolderName), fn ($f) => str_ends_with(strtolower($f), '.zip'));
                    if (! empty($found)) {
                        $allFiles = array_merge($allFiles, $found);
                    }
                } catch (\Throwable $e) {
                    // Abaikan jika folder spesifik tidak ditemukan
                }
            }

            // 2. Jika belum ditemukan atau folder kosong, scan seluruh root bucket
            if (empty($allFiles)) {
                try {
                    $found = array_filter($disk->allFiles(''), fn ($f) => str_ends_with(strtolower($f), '.zip'));
                    if (! empty($found)) {
                        $allFiles = array_merge($allFiles, $found);
                    }
                } catch (\Throwable $e) {
                    $this->error("Gagal membaca daftar file dari disk [{$diskName}]: " . $e->getMessage());
                    return self::FAILURE;
                }
            }

            $allFiles = array_values(array_unique($allFiles));

            if (empty($allFiles)) {
                $this->error("Tidak ditemukan file backup ZIP pada disk [{$diskName}] (Bucket: " . ($currentBucket ?: 'default') . ").");
                return self::FAILURE;
            }

            // Urutkan dari yang terbaru
            usort($allFiles, fn ($a, $b) => $disk->lastModified($b) <=> $disk->lastModified($a));

            $choices = [];
            $fileList = [];
            foreach ($allFiles as $index => $file) {
                $baseName = basename($file);
                $size = $this->formatBytes($disk->size($file));
                $modified = Carbon::createFromTimestamp($disk->lastModified($file))
                    ->setTimezone(config('app.timezone', 'Asia/Jakarta'))
                    ->format('d/m/Y H:i:s');

                $dirPrefix = dirname($file);
                $prefixStr = ($dirPrefix && $dirPrefix !== '.') ? "[{$dirPrefix}] " : '';
                $label = sprintf('%-45s | %10s | %s', $prefixStr . $baseName, $size, $modified);
                $choices[$index] = $label;
                $fileList[$index] = $file;
            }

            $selectedLabel = $this->choice('Pilih file backup yang ingin direstore:', $choices, 0);
            
            // Resolusi file terpilih berdasarkan index pilihan
            $selectedIndex = 0;
            foreach ($choices as $idx => $lbl) {
                if ($lbl === $selectedLabel || trim($lbl) === trim($selectedLabel)) {
                    $selectedIndex = $idx;
                    break;
                }
            }
            $selectedFile = $fileList[$selectedIndex] ?? $allFiles[0];
            $this->info("File terpilih: {$selectedFile}");
            $targetFilePath = $this->resolveStorageFilePath($diskName, $selectedFile, $tempCreated);
        }

        if (! $targetFilePath || ! file_exists($targetFilePath)) {
            $this->error('File target backup tidak dapat diakses atau gagal diunduh dari remote storage.');
            return self::FAILURE;
        }

        $isProduction = app()->environment('production', 'prod');
        $dbConfig = config('database.connections.pgsql', []);
        $dbName = $dbConfig['database'] ?? env('DB_DATABASE', 'portal_db');
        $dbHost = $dbConfig['host'] ?? env('DB_HOST', '127.0.0.1');

        $this->newLine();
        if ($isProduction) {
            $this->error('======================================================================');
            $this->error('  ⚠️  PERINGATAN BAHAYA KRITIS: RESTORE DI SERVER PRODUKSI (PRODUCTION) ');
            $this->error('======================================================================');
            $this->warn(' Anda sedang menjalankan perintah RESTORE pada server LIVE PRODUKSI.');
            $this->newLine();
            $this->error(' RINCIAN RISIKO & BAHAYA:');
            $this->line(' 1. <fg=red;options=bold>DATABASE WIPE:</> Seluruh database saat ini [<comment>' . $dbName . '</comment>] akan');
            $this->line('    DIKOSONGKAN TOTAL (db:wipe) dan digantikan oleh snapshot arsip backup.');
            $this->line(' 2. <fg=red;options=bold>KEHILANGAN DATA TERKINI:</> Semua data absensi, nilai rapor, jurnal mengajar,');
            $this->line('    jawaban/sesi ujian CBT, dan aktivitas yang tercatat SETELAH tanggal backup');
            $this->line('    dibuat akan <fg=red;options=bold>HILANG PERMANEN</> dan tidak dapat dipulihkan otomatis.');
            $this->line(' 3. <fg=red;options=bold>PENIMPAAN BERKAS:</> Berkas unggahan (foto profil, lampiran, dokumen)');
            $this->line('    di storage/app/public & public/uploads akan ditimpa dengan data arsip lama.');
            $this->line(' 4. <fg=red;options=bold>DOWNTIME APLIKASI:</> Portal akan diubah ke mode maintenance (down)');
            $this->line('    sehingga pengguna tidak dapat mengakses sistem selama proses berlangsung.');
            $this->error('======================================================================');
            $this->line(" File Target : <comment>{$targetFilePath}</comment>");
            $this->line(" Ukuran File : <comment>" . $this->formatBytes(filesize($targetFilePath)) . "</comment>");
            $this->line(" Database    : <comment>{$dbName}</comment> (Host: {$dbHost})");
            $this->line(" Environment : <fg=red;options=bold>PRODUCTION</>");
            $this->error('======================================================================');
            $this->newLine();

            if (! $this->option('force')) {
                if ($this->input->isInteractive()) {
                    $this->warn(" Untuk melanjutkan di server PRODUKSI, Anda WAJIB mengonfirmasi dengan mengetik nama database.");
                    $inputDb = $this->ask(" Ketik persis nama database [<comment>{$dbName}</comment>] untuk melanjutkan");

                    if ($inputDb !== $dbName) {
                        $this->error(" Konfirmasi nama database tidak sesuai ('{$inputDb}' !== '{$dbName}').");
                        $this->info(" Operasi restore PRODUKSI DIBATALKAN demi keamanan data.");
                        if ($tempCreated && file_exists($targetFilePath)) {
                            @unlink($targetFilePath);
                        }
                        return self::FAILURE;
                    }
                    $this->info(" Konfirmasi nama database cocok. Melanjutkan ke proses restore...");
                } else {
                    $this->error(' Operasi restore di environment produksi dibatalkan (memerlukan konfirmasi interaktif atau opsi --force).');
                    if ($tempCreated && file_exists($targetFilePath)) {
                        @unlink($targetFilePath);
                    }
                    return self::FAILURE;
                }
            }
        } else {
            // Environment Staging / Development / Local
            $this->info('------------------------------------------------------');
            $this->info(' Lingkungan : STAGING / DEVELOPMENT (' . app()->environment() . ')');
            $this->line(" File Target: <comment>{$targetFilePath}</comment> (" . $this->formatBytes(filesize($targetFilePath)) . ')');
            $this->info('------------------------------------------------------');
            $this->newLine();

            if (! $this->option('force') && ! $this->confirm('Apakah Anda ingin melanjutkan proses restore database ini?', true)) {
                $this->info('Operasi restore dibatalkan oleh pengguna.');
                if ($tempCreated && file_exists($targetFilePath)) {
                    @unlink($targetFilePath);
                }
                return self::SUCCESS;
            }
        }

        // 3. Eksekusi Restore
        try {
            $status = $this->executeRestore($targetFilePath);
            return $status ? self::SUCCESS : self::FAILURE;
        } finally {
            if ($tempCreated && file_exists($targetFilePath)) {
                @unlink($targetFilePath);
            }
        }
    }

    /**
     * Dapatkan path lokal dari file storage (download jika remote disk).
     */
    private function resolveStorageFilePath(string $diskName, string $relativePath, bool &$tempCreated): string
    {
        $driver = config("filesystems.disks.{$diskName}.driver", 'local');

        // Jika disk adalah 'local', kita bisa gunakan path fisik langsung
        if ($driver === 'local') {
            try {
                $localPath = Storage::disk($diskName)->path($relativePath);
                if (file_exists($localPath)) {
                    return $localPath;
                }
            } catch (\Throwable $e) {
                // fallback ke download jika gagal
            }
        }

        // Untuk Remote Storage (S3 / MinIO / dll), unduh file secara streaming ke temporary dengan Progress Bar
        $disk = Storage::disk($diskName);
        $fileSize = 0;
        try {
            $fileSize = $disk->size($relativePath);
        } catch (\Throwable $e) {
            // Abaikan jika tidak bisa ambil size
        }

        $this->info("Memulai pengunduhan file backup dari storage [{$diskName}] (" . ($fileSize ? $this->formatBytes($fileSize) : 'Unknown size') . ")...");

        $tempDir = storage_path('app/backup-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $fileName = basename($relativePath);
        $localPath = $tempDir . '/cli-restore-temp-' . time() . '-' . $fileName;

        @set_time_limit(0);

        // Tampilkan Progress Bar interaktif
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->setFormat(" %message%\n [%bar%] %percent:3s%% [Waktu: %elapsed%]");
        $progressBar->setMessage("<comment>📥 Mengunduh: 0 B / " . $this->formatBytes($fileSize) . "</comment>");
        $progressBar->start();

        // 1. Jika S3 driver, gunakan native S3Client SaveAs dengan cURL/Guzzle real-time progress callback (Sangat cepat & real-time)
        if ($driver === 's3' && method_exists($disk, 'getClient')) {
            $bucket = config("filesystems.disks.{$diskName}.bucket");
            $lastUpdate = 0;

            try {
                $disk->getClient()->getObject([
                    'Bucket' => $bucket,
                    'Key' => $relativePath,
                    'SaveAs' => $localPath,
                    '@http' => [
                        'progress' => function ($dlTotal, $dlNow) use ($progressBar, $fileSize, &$lastUpdate) {
                            $targetTotal = $dlTotal > 0 ? $dlTotal : $fileSize;
                            if ($targetTotal > 0 && (microtime(true) - $lastUpdate > 0.25 || $dlNow === $targetTotal)) {
                                $lastUpdate = microtime(true);
                                $pct = (int) min(100, floor(($dlNow / $targetTotal) * 100));
                                $progressBar->setProgress($pct);
                                $progressBar->setMessage("<comment>📥 Mengunduh: " . $this->formatBytes($dlNow) . " / " . $this->formatBytes($targetTotal) . "</comment>");
                            }
                        }
                    ]
                ]);

                $progressBar->setProgress(100);
                $progressBar->finish();
                $tempCreated = true;
                $this->newLine(2);
                $actualSize = file_exists($localPath) ? filesize($localPath) : $fileSize;
                $this->info("✅ Selesai mengunduh (" . $this->formatBytes($actualSize) . ") ke: {$localPath}");
                return $localPath;
            } catch (\Throwable $e) {
                // Fallback ke generic stream jika SaveAs gagal
            }
        }

        // 2. Generic chunked stream fallback
        $readStream = $disk->readStream($relativePath);
        if (! $readStream) {
            throw new \Exception("Gagal membuka read stream dari remote disk [{$diskName}]: {$relativePath}");
        }

        $writeHandle = fopen($localPath, 'wb');
        if (! $writeHandle) {
            if (is_resource($readStream)) fclose($readStream);
            throw new \Exception("Gagal membuat temporary file lokal: {$localPath}");
        }

        $chunkSize = 1024 * 1024 * 2; // 2 MB per chunk
        $downloaded = 0;

        while (! feof($readStream)) {
            $buffer = fread($readStream, $chunkSize);
            if ($buffer === false) break;
            $written = fwrite($writeHandle, $buffer);
            if ($written === false) break;
            $downloaded += $written;

            $pct = $fileSize > 0 ? (int) min(100, floor(($downloaded / $fileSize) * 100)) : 0;
            $progressBar->setProgress($pct);
            $progressBar->setMessage("<comment>📥 Mengunduh: " . $this->formatBytes($downloaded) . " / " . $this->formatBytes($fileSize) . "</comment>");
        }

        $progressBar->setProgress(100);
        $progressBar->finish();
        fclose($writeHandle);
        if (is_resource($readStream)) {
            fclose($readStream);
        }

        $tempCreated = true;
        $this->newLine(2);
        $this->info("✅ Selesai mengunduh (" . $this->formatBytes($downloaded) . ") ke: {$localPath}");
        return $localPath;
    }

    /**
     * Proses inti restore dari file ZIP.
     */
    private function executeRestore(string $sourcePath): bool
    {
        $dbConfig = config('database.connections.pgsql');
        $tempPath = storage_path('app/backup-temp/cli-restore-' . time());
        $safetyDumpPath = null;

        $password = $this->option('password') ?: config('backup.backup.password');
        if (empty($password)) {
            $this->warn('Peringatan: Password arsip backup belum diatur di .env (BACKUP_ARCHIVE_PASSWORD).');
        }

        try {
            @set_time_limit(0);
            if (function_exists('ini_set')) {
                @ini_set('max_execution_time', '0');
            }

            $this->info('1/7. Mengaktifkan mode maintenance (app down)...');
            Artisan::call('down');

            // 1. Ekstrak ZIP
            $this->info('2/7. Membuka dan mengekstrak file backup ZIP...');
            File::makeDirectory($tempPath, 0755, true, true);

            $zip = new ZipArchive();
            $res = $zip->open($sourcePath);

            if ($res !== true) {
                throw new \Exception('Gagal membuka file ZIP. Kode: ' . $res);
            }

            if ($password) {
                $zip->setPassword($password);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                if (str_ends_with($filename, '/')) {
                    continue;
                }

                if (str_ends_with(strtolower($filename), '.sql.gz')) {
                    $ext = '.sql.gz';
                    $base = substr($filename, 0, -strlen('.sql.gz'));
                } elseif (str_ends_with(strtolower($filename), '.sql')) {
                    $ext = '.sql';
                    $base = substr($filename, 0, -strlen('.sql'));
                } else {
                    $ext = '';
                    $base = $filename;
                }
                $safeName = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $base) . $ext;
                $osPath = str_replace('/', DIRECTORY_SEPARATOR, $safeName);
                $fullPath = $tempPath . DIRECTORY_SEPARATOR . $osPath;
                $dir = dirname($fullPath);

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
                    throw new \Exception("Gagal mengekstrak isi backup [{$filename}]. Kemungkinan password ZIP salah.");
                }
            }
            $zip->close();

            // 2. Temukan file SQL dump & manifest.json
            $allFiles = File::allFiles($tempPath);
            $backupFile = null;
            $manifestFile = null;

            foreach ($allFiles as $file) {
                $lower = strtolower($file->getFilename());
                if (str_ends_with($lower, '.sql.gz') || str_ends_with($lower, '.sql')) {
                    $backupFile = $file->getPathname();
                }
                if ($lower === 'manifest.json') {
                    $manifestFile = $file->getPathname();
                }
            }

            if (! $backupFile) {
                throw new \Exception('File dump database (.sql / .sql.gz) tidak ditemukan di dalam arsip ZIP.');
            }

            // 3. Verifikasi Manifest & Versi Migrasi
            $this->info('3/7. Memvalidasi manifest backup dan skema database...');
            $isForce = (bool) $this->option('force');

            if (! $manifestFile) {
                $this->warn('File manifest.json tidak ditemukan (Arsip Backup Legacy).');
                if (! $isForce) {
                    if (! $this->confirm('Versi skema tidak terdeteksi. Lanjutkan dengan Force Restore?', false)) {
                        throw new \Exception('Restore dibatalkan: Manifest tidak ada dan Force Restore tidak disetujui.');
                    }
                }
            } else {
                $manifestData = json_decode(file_get_contents($manifestFile), true);
                $latestMigration = DB::table('migrations')->orderBy('migration', 'desc')->first();
                $currentDbVersion = $latestMigration ? $latestMigration->migration : 'N/A';
                $backupDbVersion = $manifestData['db_migration_version'] ?? 'N/A';

                $this->line(" - Versi Migrasi Backup: <comment>{$backupDbVersion}</comment>");
                $this->line(" - Versi Migrasi Server: <comment>{$currentDbVersion}</comment>");

                if ($backupDbVersion !== $currentDbVersion) {
                    $this->warn("Perbedaan versi skema database terdeteksi!");
                    if (! $isForce) {
                        if (! $this->confirm('Versi migrasi berbeda. Tetap lanjutkan restore?', false)) {
                            throw new \Exception("Restore dibatalkan: Versi migrasi backup [{$backupDbVersion}] tidak sesuai dengan server [{$currentDbVersion}].");
                        }
                    }
                } else {
                    $this->info(' Validasi versi skema database cocok.');
                }
            }

            // 4. Dekompresi GZIP jika perlu
            $sqlFilePath = $backupFile;
            if (str_ends_with(strtolower($backupFile), '.gz')) {
                $this->info(' Mendekompresi file dump gzip...');
                $sqlFilePath = preg_replace('/\.gz$/i', '', $backupFile);
                $this->unzipGzip($backupFile, $sqlFilePath);
            }

            // Sanitasi file SQL dump
            $this->info(' Mensanitasi query SQL (membersihkan OWNER/ROLE/PRIVILEGES)...');
            $this->sanitizeSqlDumpFile($sqlFilePath);

            // 5. Buat Safety Dump sebelum WIPE
            $this->info('4/7. Membuat Safety Dump kondisi saat ini untuk rollback otomatis jika terjadi kegagalan...');
            $safetyDumpPath = storage_path('app/backup-temp/pre-restore-safety-cli-' . time() . '.sql');

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
                throw new \Exception('Gagal membuat safety dump: ' . implode(' | ', array_slice($safetyOutput, -5)));
            }

            $this->sanitizeSqlDumpFile($safetyDumpPath);
            $this->info(' Safety Dump berhasil dibuat.');

            // 6. Kosongkan database & Restore SQL via psql
            $this->info('5/7. Mengosongkan database saat ini (db:wipe)...');
            Artisan::call('db:wipe', ['--force' => true]);

            $this->info(' Menjalankan restore database PostgreSQL melalui psql...');
            $psqlBin = 'psql';
            $command = sprintf(
                '%s -v ON_ERROR_STOP=1 -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellcmd($psqlBin),
                escapeshellarg($dbConfig['host']),
                escapeshellarg((string) ($dbConfig['port'] ?? '5432')),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($sqlFilePath)
            );

            putenv("PGPASSWORD={$dbConfig['password']}");
            exec($command, $output, $returnVar);
            putenv('PGPASSWORD');

            if ($returnVar !== 0) {
                $this->error(' Eksekusi psql gagal! Memulai rollback otomatis dari safety dump...');
                Artisan::call('db:wipe', ['--force' => true]);

                $rollbackCommand = sprintf(
                    'psql -v ON_ERROR_STOP=1 -h %s -p %s -U %s -d %s -f %s 2>&1',
                    escapeshellcmd($psqlBin),
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
                    throw new \Exception('Restore gagal, tetapi database telah berhasil di-rollback ke kondisi semula. Output error: ' . implode(' | ', array_slice($output, -5)));
                }

                throw new \Exception('KRITIS: Restore gagal DAN rollback safety dump juga gagal! File safety dump tersimpan di: ' . $safetyDumpPath);
            }

            $this->info(' Database PostgreSQL berhasil dipulihkan.');

            // Safety dump tidak diperlukan lagi jika sukses
            if ($safetyDumpPath && file_exists($safetyDumpPath)) {
                @unlink($safetyDumpPath);
            }

            // 7. Pulihkan file fisik (storage/app/public & public/uploads)
            if (! $this->option('skip-files')) {
                $this->info('6/7. Memulihkan berkas storage dan uploads...');
                $allDirectories = File::allDirectories($tempPath);
                $restoredFolders = 0;

                foreach ($allDirectories as $dir) {
                    $normalizedDir = str_replace('\\', '/', $dir);

                    if (str_ends_with($normalizedDir, 'storage/app/public') || str_ends_with($normalizedDir, 'public/storage')) {
                        File::copyDirectory($dir, storage_path('app/public'));
                        $restoredFolders++;
                    }

                    if (preg_match('#public/uploads(/.*)?$#', $normalizedDir)) {
                        $destSuffix = preg_replace('#^.*public/uploads#', '', $normalizedDir);
                        $dest = public_path('uploads' . $destSuffix);
                        if (! is_dir($dest)) {
                            mkdir($dest, 0777, true);
                        }
                        File::copyDirectory($dir, $dest);
                        $restoredFolders++;
                    }
                }
                $this->info(" File storage berhasil disinkronkan ({$restoredFolders} folder diproses).");
            } else {
                $this->info('6/7. Opsi --skip-files aktif, pemulihan berkas storage dilewati.');
            }

            // 8. Bersih-bersih & Finalisasi
            $this->info('7/7. Membersihkan temporary cache dan mengaktifkan kembali aplikasi...');
            $this->fastDeleteDirectory($tempPath);
            Artisan::call('optimize:clear');
            Artisan::call('up');

            $stats = $this->getRestoreStats();
            ActivityLogger::log('RESTORE', "CLI: Database berhasil direstore dari " . basename($sourcePath) . ". {$stats}");

            $this->newLine();
            $this->info('======================================================');
            $this->info('          RESTORE BACKUP BERHASIL DISELESAIKAN!       ');
            $this->info('======================================================');
            $this->line("Statistik Pemulihan: <info>{$stats}</info>");
            $this->newLine();

            return true;

        } catch (\Throwable $e) {
            if (File::isDirectory($tempPath)) {
                $this->fastDeleteDirectory($tempPath);
            }
            Artisan::call('up');
            Log::error('CLI Restore Error: ' . $e->getMessage());

            $this->newLine();
            $this->error('======================================================');
            $this->error('               RESTORE GAGAL DILAKUKAN                ');
            $this->error('======================================================');
            $this->error($e->getMessage());
            $this->newLine();

            return false;
        }
    }

    /**
     * Hitung ringkasan data setelah proses restore.
     */
    private function getRestoreStats(): string
    {
        try {
            $parts = [];
            if (DB::getSchemaBuilder()->hasTable('users')) {
                $parts[] = DB::table('users')->count() . ' User';
            }
            if (DB::getSchemaBuilder()->hasTable('students')) {
                $parts[] = DB::table('students')->count() . ' Siswa';
            }
            if (DB::getSchemaBuilder()->hasTable('teachers')) {
                $parts[] = DB::table('teachers')->count() . ' Guru';
            }
            if (DB::getSchemaBuilder()->hasTable('cbt_questions')) {
                $parts[] = DB::table('cbt_questions')->count() . ' Soal CBT';
            }
            return empty($parts) ? 'Database berhasil dipulihkan.' : 'Terdeteksi: ' . implode(', ', $parts);
        } catch (\Throwable $e) {
            return 'Database berhasil dipulihkan.';
        }
    }

    /**
     * Helper unzip Gzip
     */
    private function unzipGzip(string $source, string $dest): void
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
     * Sanitasi dump SQL dari perintah superuser / OWNER / PRIVILEGES
     */
    private function sanitizeSqlDumpFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $tempSanitizedPath = $filePath . '.sanitized';
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

            if (
                preg_match('/^SET\s+(ROLE|SESSION\s+AUTHORIZATION)\s+/i', $trimmed) ||
                preg_match('/^ALTER\s+.*?\s+OWNER\s+TO\s+/i', $trimmed) ||
                preg_match('/^(GRANT|REVOKE)\s+/i', $trimmed) ||
                preg_match('/^ALTER\s+DEFAULT\s+PRIVILEGES\s+/i', $trimmed)
            ) {
                fwrite($out, "-- [SANITIZED BY PORTAL SMA] " . $line);
                continue;
            }

            fwrite($out, $line);
        }

        fclose($in);
        fclose($out);

        @unlink($filePath);
        rename($tempSanitizedPath, $filePath);
    }

    /**
     * Fast directory delete
     */
    private function fastDeleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $escaped = escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $path));
                exec("rmdir /s /q {$escaped} 2>nul");
            } else {
                $escaped = escapeshellarg($path);
                exec("rm -rf {$escaped} 2>/dev/null");
            }
        } catch (\Throwable $e) {
            // Ignored
        }

        if (is_dir($path)) {
            try {
                File::deleteDirectory($path);
            } catch (\Throwable $e) {
                // Ignored
            }
        }
    }

    /**
     * Format bytes ke format human readable
     */
    private function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Pengecekan ketersediaan command pg_dump & psql
     */
    private function checkSystemRequirements(): bool
    {
        $hasPgDump = $this->checkViaCommand('pg_dump');
        $hasPsql = $this->checkViaCommand('psql');
        $storageWritable = is_writable(storage_path('app'));

        if (! $hasPgDump) {
            $this->error("ERROR: Binary 'pg_dump' tidak ditemukan di environment PATH.");
        }
        if (! $hasPsql) {
            $this->error("ERROR: Binary 'psql' tidak ditemukan di environment PATH.");
        }
        if (! $storageWritable) {
            $this->error("ERROR: Direktori storage/app tidak memiliki izin tulis.");
        }

        return $hasPgDump && $hasPsql && $storageWritable;
    }

    /**
     * Cek keberadaan binary via PATH
     */
    private function checkViaCommand(string $binary): bool
    {
        $this->injectPgBinToPath();
        $command = PHP_OS_FAMILY === 'Windows'
            ? "where {$binary} 2>nul"
            : "command -v {$binary} 2>/dev/null";

        exec($command, $output, $returnVar);
        return $returnVar === 0;
    }

    /**
     * Injeksi direktori bin PostgreSQL ke PATH
     */
    private function injectPgBinToPath(): void
    {
        $configPath = config('database.connections.pgsql.dump.dump_binary_path');
        $binDir = ($configPath && is_dir($configPath)) ? $configPath : null;

        if (! $binDir) {
            $cmd = PHP_OS_FAMILY === 'Windows' ? 'where pg_dump 2>nul' : 'which pg_dump 2>/dev/null';
            exec($cmd, $output, $returnVar);
            if ($returnVar === 0 && ! empty($output[0])) {
                $binDir = dirname(trim($output[0]));
            }
        }

        if ($binDir) {
            $currentPath = getenv('PATH') ?: '';
            $separator = PHP_OS_FAMILY === 'Windows' ? ';' : ':';
            if (! str_contains($currentPath, $binDir)) {
                putenv("PATH={$binDir}{$separator}{$currentPath}");
            }
        }
    }
}
