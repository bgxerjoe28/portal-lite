<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

class CopyMinioBucketCommand extends Command
{
    protected $signature = 'storage:copy-bucket 
                            {source=portal-sma-staging : Nama bucket asal} 
                            {target=portal-sma-production : Nama bucket tujuan}
                            {--find= : Cari nama file tertentu di kedua bucket}
                            {--summary : Tampilkan ringkasan folder dan jumlah file di kedua bucket}';

    protected $description = 'Salin seluruh file dari bucket MinIO asal ke bucket tujuan secara server-side';

    public function handle(): int
    {
        $source = $this->argument('source');
        $target = $this->argument('target');

        $this->info("Menghubungkan ke MinIO S3 Server...");

        $config = config('filesystems.disks.s3_local');
        
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $config['region'] ?? 'us-east-1',
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? true,
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'http' => [
                'verify' => false,
            ],
        ]);

        if ($this->option('summary')) {
            $this->info("Menghitung ringkasan isi bucket...");
            foreach ([$source, $target] as $b) {
                $folders = [];
                $totalFiles = 0;
                $paginator = $s3->getPaginator('ListObjectsV2', ['Bucket' => $b]);
                foreach ($paginator as $page) {
                    foreach ($page['Contents'] ?? [] as $obj) {
                        $totalFiles++;
                        $parts = explode('/', $obj['Key']);
                        $folderName = count($parts) > 1 ? $parts[0] . '/' : '[root]';
                        $folders[$folderName] = ($folders[$folderName] ?? 0) + 1;
                    }
                }
                $this->info("\n--- BUCKET: [{$b}] (Total: {$totalFiles} file) ---");
                foreach ($folders as $f => $count) {
                    $this->line("  📁 {$f} : {$count} file");
                }
            }
            return self::SUCCESS;
        }

        $searchTerm = $this->option('find');
        if ($searchTerm) {
            $this->info("Mencari file dengan kata kunci [{$searchTerm}] di bucket [{$source}] dan [{$target}]...");

            // Cek di source
            $sourceMatches = [];
            $paginator = $s3->getPaginator('ListObjectsV2', ['Bucket' => $source]);
            foreach ($paginator as $page) {
                foreach ($page['Contents'] ?? [] as $obj) {
                    if (str_contains($obj['Key'], $searchTerm)) {
                        $sourceMatches[] = $obj['Key'];
                    }
                }
            }
            $this->info("Hasil di [{$source}] (" . count($sourceMatches) . " ditemukan):");
            foreach ($sourceMatches as $k) {
                $this->line("  -> {$k}");
            }

            // Cek di target
            $targetMatches = [];
            $paginator = $s3->getPaginator('ListObjectsV2', ['Bucket' => $target]);
            foreach ($paginator as $page) {
                foreach ($page['Contents'] ?? [] as $obj) {
                    if (str_contains($obj['Key'], $searchTerm)) {
                        $targetMatches[] = $obj['Key'];
                    }
                }
            }
            $this->info("Hasil di [{$target}] (" . count($targetMatches) . " ditemukan):");
            foreach ($targetMatches as $k) {
                $this->line("  -> {$k}");
            }

            // Jika ada di source tapi tidak ada di target, copy langsung
            $missingInTarget = array_diff($sourceMatches, $targetMatches);
            if (!empty($missingInTarget)) {
                $this->info("Menyalin " . count($missingInTarget) . " file yang belum ada di target...");
                foreach ($missingInTarget as $key) {
                    $s3->copyObject([
                        'Bucket' => $target,
                        'Key' => $key,
                        'CopySource' => rawurlencode("{$source}/{$key}"),
                    ]);
                    $this->info("  [COPIED] {$key}");
                }
            }

            return self::SUCCESS;
        }

        // 1. Cek / Buat target bucket jika belum ada
        try {
            $buckets = $s3->listBuckets();
            $existingBuckets = array_column($buckets['Buckets'] ?? [], 'Name');

            $this->info("Daftar bucket yang ada di MinIO: " . implode(', ', $existingBuckets));

            if (!in_array($target, $existingBuckets)) {
                $this->info("Bucket tujuan [{$target}] belum ada. Membuat bucket baru...");
                $s3->createBucket(['Bucket' => $target]);
                $this->info("Bucket [{$target}] berhasil dibuat!");

                // Salin policy jika ada
                try {
                    $policy = $s3->getBucketPolicy(['Bucket' => $source]);
                    if (!empty($policy['Policy'])) {
                        $targetPolicy = str_replace($source, $target, (string) $policy['Policy']);
                        $s3->putBucketPolicy([
                            'Bucket' => $target,
                            'Policy' => $targetPolicy,
                        ]);
                        $this->info("Bucket Policy berhasil diterapkan ke [{$target}].");
                    }
                } catch (\Throwable $e) {
                    // Policy optional
                }
            } else {
                $this->info("Bucket tujuan [{$target}] sudah tersedia.");
            }
        } catch (\Throwable $e) {
            $this->error("Gagal memeriksa/membuat bucket: " . $e->getMessage());
            return self::FAILURE;
        }

        // 2. Ambil semua file dari source bucket
        $this->info("Mengambil daftar file dari [{$source}]...");
        $files = [];
        $continuationToken = null;

        do {
            $params = [
                'Bucket' => $source,
            ];
            if ($continuationToken) {
                $params['ContinuationToken'] = $continuationToken;
            }

            $res = $s3->listObjectsV2($params);
            if (!empty($res['Contents'])) {
                foreach ($res['Contents'] as $object) {
                    $files[] = $object['Key'];
                }
            }
            $continuationToken = $res['NextContinuationToken'] ?? null;
        } while ($continuationToken);

        $total = count($files);
        if ($total === 0) {
            $this->warn("Tidak ada file ditemukan di bucket [{$source}].");
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$total} file di bucket [{$source}]. Memulai proses penyalinan ke [{$target}]...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($files as $key) {
            try {
                $s3->copyObject([
                    'Bucket' => $target,
                    'Key' => $key,
                    'CopySource' => rawurlencode("{$source}/{$key}"),
                ]);
                $successCount++;
            } catch (\Throwable $e) {
                $failCount++;
                $errors[] = "Gagal copy [{$key}]: " . $e->getMessage();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("==========================================");
        $this->info("Proses penyalinan selesai!");
        $this->info("Berhasil disalin : {$successCount} file");
        if ($failCount > 0) {
            $this->warn("Gagal disalin    : {$failCount} file");
            foreach (array_slice($errors, 0, 5) as $err) {
                $this->error($err);
            }
        }
        $this->info("==========================================");

        return self::SUCCESS;
    }
}
