<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessActivityLogJob implements ShouldQueue
{
    use Queueable;

    public array $logData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $logData)
    {
        $this->logData = $logData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            ActivityLog::create($this->logData);
        } catch (\Exception $e) {
            // Jangan biarkan kegagalan logging menghentikan job lain secara fatal
            Log::error('Background Job: Gagal mencatat ActivityLog: ' . $e->getMessage());
        }
    }
}
