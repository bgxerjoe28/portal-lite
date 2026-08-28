<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogger;

class SyncBackupToS3Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan ulang jika MinIO bermasalah.
     */
    public int $tries = 3;

    /**
     * Timeout upload untuk file besar (misal 2GB bisa butuh waktu lama).
     */
    public int $timeout = 1800; // 30 menit

    public function __construct(private string $fileName) {}

    public function handle(): void
    {
        $backupFolderName = config('backup.backup.name');
        $path = $backupFolderName . '/' . $this->fileName;

        // 1. Pastikan file ada di lokal
        if (!Storage::disk('local')->exists($path)) {
            Log::warning("SyncBackupToS3Job: File backup lokal tidak ditemukan, skip.", [
                'file' => $this->fileName
            ]);
            return;
        }

        // 2. Pastikan konfigurasi S3 aktif
        $targetDisk = 's3_local';
        if (empty(config("filesystems.disks.{$targetDisk}.key"))) {
            Log::warning("SyncBackupToS3Job: Kredensial S3 belum dikonfigurasi, skip.");
            return;
        }

        try {
            // 3. Streaming file lokal ke S3 (hemat memori)
            $fileStream = Storage::disk('local')->readStream($path);
            
            // Upload
            Storage::disk($targetDisk)->writeStream($path, $fileStream);

            ActivityLogger::log('BACKUP', "File backup otomatis disinkronisasi ke S3: {$this->fileName}");
            Log::info("SyncBackupToS3Job: Berhasil mengirim backup ke S3: {$this->fileName}");

        } catch (\Exception $e) {
            Log::error('SyncBackupToS3Job Error: ' . $e->getMessage());
            // Lemparkan exception agar worker menganggap job ini gagal dan otomatis retry
            throw $e;
        }
    }
}
