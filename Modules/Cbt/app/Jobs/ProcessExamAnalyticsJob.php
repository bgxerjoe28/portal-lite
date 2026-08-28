<?php

namespace Modules\Cbt\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Cbt\Services\CttAnalyticsService;
use Modules\Cbt\Services\IrtMicroserviceClient;
use Illuminate\Support\Facades\Log;

class ProcessExamAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $examId;
    public string $irtModelType;

    /**
     * Create a new job instance.
     */
    public function __construct(int $examId, string $irtModelType = '2PL')
    {
        $this->examId = $examId;
        $this->irtModelType = $irtModelType;
    }

    /**
     * Execute the job.
     */
    public function handle(CttAnalyticsService $cttService, IrtMicroserviceClient $irtClient): void
    {
        Log::info("Starting ProcessExamAnalyticsJob for Exam ID: {$this->examId}");

        // 1. Process CTT Analytics (Always computed)
        $cttResult = $cttService->calculateExamCtt($this->examId);
        Log::info("CTT Analytics completed for Exam ID {$this->examId}", $cttResult);

        // 2. Process IRT Analytics (Only if N >= 100 participants)
        $irtResult = $irtClient->dispatchIrtEstimation($this->examId, $this->irtModelType);
        Log::info("IRT Microservice dispatch completed for Exam ID {$this->examId}", $irtResult);
    }
}
