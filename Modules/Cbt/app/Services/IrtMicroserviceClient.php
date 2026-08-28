<?php

namespace Modules\Cbt\Services;

use Modules\Cbt\Models\CbtBank;
use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Cbt\Models\CbtAnalysisJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IrtMicroserviceClient
{
    /**
     * Minimum participants required for IRT estimation (Rasch / 2PL / 3PL)
     */
    public const MIN_PARTICIPANTS = 100;

    /**
     * Dispatch IRT Estimation request to Python Microservice asynchronously.
     */
    public function dispatchIrtEstimation(int $examId, string $modelType = '2PL'): array
    {
        $exam = CbtExam::with('bank.questions')->findOrFail($examId);
        $questions = $exam->bank->questions;

        // Ambil semua peserta yang sudah submit atau memiliki pengerjaan/jawaban
        $studentExams = CbtStudentExam::where('cbt_exam_id', $examId)
            ->where(function ($q) {
                $q->whereIn('status', ['submitted', 'completed', 'started', 'login'])
                  ->orWhereHas('answers');
            })
            ->get();

        $totalN = $studentExams->count();

        // 1. Terapkan Aturan Threshold N >= 100 Peserta
        if ($totalN < self::MIN_PARTICIPANTS) {
            Log::info("IRT Estimation skipped for exam {$examId}: Participants count ({$totalN}) < " . self::MIN_PARTICIPANTS);

            CbtAnalysisJob::updateOrCreate(
                [
                    'cbt_exam_id' => $examId,
                    'job_type' => 'IRT_' . strtoupper($modelType),
                ],
                [
                    'status' => 'skipped',
                    'total_participants' => $totalN,
                    'progress_percent' => 0,
                    'error_message' => "Peserta kurang dari " . self::MIN_PARTICIPANTS . " (Total: {$totalN}). Analisis IRT membutuhkan min. 100 peserta untuk konvergensi.",
                    'completed_at' => now(),
                ]
            );

            return [
                'status' => 'skipped',
                'reason' => 'insufficient_participants',
                'total_participants' => $totalN,
                'min_required' => self::MIN_PARTICIPANTS,
            ];
        }

        // 2. Buat Tracking Job Status
        $job = CbtAnalysisJob::updateOrCreate(
            [
                'cbt_exam_id' => $examId,
                'job_type' => 'IRT_' . strtoupper($modelType),
            ],
            [
                'status' => 'processing',
                'total_participants' => $totalN,
                'progress_percent' => 10,
                'started_at' => now(),
                'error_message' => null,
            ]
        );

        // 3. Konstruksi Matriks Respon N x M
        $answersGrouped = CbtStudentAnswer::whereIn('cbt_student_exam_id', $studentExams->pluck('id'))
            ->get()
            ->groupBy('cbt_student_exam_id');

        $questionList = [];
        foreach ($questions as $q) {
            $questionList[] = [
                'id' => $q->id,
                'score_max' => (float) $q->score,
            ];
        }

        $responsesList = [];
        foreach ($studentExams as $stExam) {
            $stAns = $answersGrouped->get($stExam->id) ? $answersGrouped->get($stExam->id)->keyBy('cbt_question_id') : collect();
            $userAnswers = [];

            foreach ($questions as $q) {
                $ans = $stAns->get($q->id);
                $isCorrect = ($ans && $ans->is_correct === true) ? 1 : 0;
                $userAnswers[(string)$q->id] = $isCorrect;
            }

            $responsesList[] = [
                'student_exam_id' => $stExam->id,
                'answers' => $userAnswers,
            ];
        }

        // 4. Endpoint & Callback URL
        $microserviceUrl = config('cbt.irt_microservice_url', env('IRT_MICROSERVICE_URL', 'http://127.0.0.1:8085/api/v1/irt/estimate'));
        $callbackUrl = route('cbt.internal.irt_callback');

        $payload = [
            'exam_id' => $examId,
            'job_id' => $job->id,
            'model_type' => $modelType,
            'callback_url' => $callbackUrl,
            'questions' => $questionList,
            'responses' => $responsesList,
        ];

        // 5. Kirim Request Asinkron (Timeout 3 detik untuk handshake connection)
        try {
            $response = Http::timeout(3)
                ->withHeaders([
                    'X-CBT-Secret' => config('cbt.internal_secret', env('CBT_INTERNAL_SECRET', 'cbt-secret-key')),
                ])
                ->post($microserviceUrl, $payload);

            if ($response->successful()) {
                $job->update([
                    'status' => 'sent_to_microservice',
                    'progress_percent' => 30,
                ]);

                return [
                    'status' => 'sent_to_microservice',
                    'task_id' => $response->json('task_id') ?? null,
                    'total_participants' => $totalN,
                ];
            } else {
                $errMsg = "Microservice returned status " . $response->status() . ": " . $response->body();
                $job->update([
                    'status' => 'failed',
                    'error_message' => $errMsg,
                    'completed_at' => now(),
                ]);

                return [
                    'status' => 'error',
                    'message' => $errMsg,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Failed connecting to IRT Microservice: " . $e->getMessage());

            $job->update([
                'status' => 'failed',
                'error_message' => "Connection failed to IRT Microservice: " . $e->getMessage(),
                'completed_at' => now(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch Bank-Wide IRT Estimation request to Python Microservice asynchronously.
     */
    public function dispatchBankIrtEstimation(int $bankId, string $modelType = '2PL'): array
    {
        $bank = CbtBank::with('questions', 'exams')->findOrFail($bankId);
        $questions = $bank->questions;
        $examIds = $bank->exams->pluck('id')->toArray();

        if (empty($examIds)) {
            return [
                'status' => 'skipped',
                'reason' => 'no_exams_using_bank',
            ];
        }

        $studentExams = CbtStudentExam::whereIn('cbt_exam_id', $examIds)
            ->where(function ($q) {
                $q->whereIn('status', ['submitted', 'completed', 'started', 'login'])
                  ->orWhereHas('answers');
            })
            ->get();

        $totalN = $studentExams->count();

        if ($totalN < self::MIN_PARTICIPANTS) {
            Log::info("IRT Estimation skipped for Bank {$bankId}: Participants count ({$totalN}) < " . self::MIN_PARTICIPANTS);

            CbtAnalysisJob::updateOrCreate(
                [
                    'cbt_bank_id' => $bankId,
                    'job_type' => 'IRT_' . strtoupper($modelType),
                ],
                [
                    'status' => 'skipped',
                    'total_participants' => $totalN,
                    'progress_percent' => 0,
                    'error_message' => "Peserta kurang dari " . self::MIN_PARTICIPANTS . " (Total: {$totalN}). Analisis IRT membutuhkan min. 100 peserta untuk konvergensi.",
                    'completed_at' => now(),
                ]
            );

            return [
                'status' => 'skipped',
                'reason' => 'insufficient_participants',
                'total_participants' => $totalN,
                'min_required' => self::MIN_PARTICIPANTS,
            ];
        }

        $job = CbtAnalysisJob::updateOrCreate(
            [
                'cbt_bank_id' => $bankId,
                'job_type' => 'IRT_' . strtoupper($modelType),
            ],
            [
                'status' => 'processing',
                'total_participants' => $totalN,
                'progress_percent' => 10,
                'started_at' => now(),
                'error_message' => null,
            ]
        );

        $answersGrouped = CbtStudentAnswer::whereIn('cbt_student_exam_id', $studentExams->pluck('id'))
            ->get()
            ->groupBy('cbt_student_exam_id');

        $questionList = [];
        foreach ($questions as $q) {
            $questionList[] = [
                'id' => $q->id,
                'score_max' => (float) $q->score,
            ];
        }

        $responsesList = [];
        foreach ($studentExams as $stExam) {
            $stAns = $answersGrouped->get($stExam->id) ? $answersGrouped->get($stExam->id)->keyBy('cbt_question_id') : collect();
            $userAnswers = [];

            foreach ($questions as $q) {
                $ans = $stAns->get($q->id);
                $isCorrect = ($ans && $ans->is_correct === true) ? 1 : 0;
                $userAnswers[(string)$q->id] = $isCorrect;
            }

            $responsesList[] = [
                'student_exam_id' => $stExam->id,
                'answers' => $userAnswers,
            ];
        }

        $microserviceUrl = config('cbt.irt_microservice_url', env('IRT_MICROSERVICE_URL', 'http://127.0.0.1:8085/api/v1/irt/estimate'));
        $callbackUrl = route('cbt.internal.irt_callback');

        $payload = [
            'bank_id' => $bankId,
            'job_id' => $job->id,
            'model_type' => $modelType,
            'callback_url' => $callbackUrl,
            'questions' => $questionList,
            'responses' => $responsesList,
        ];

        try {
            $response = Http::timeout(3)
                ->withHeaders([
                    'X-CBT-Secret' => config('cbt.internal_secret', env('CBT_INTERNAL_SECRET', 'cbt-secret-key')),
                ])
                ->post($microserviceUrl, $payload);

            if ($response->successful()) {
                $job->update([
                    'status' => 'sent_to_microservice',
                    'progress_percent' => 30,
                ]);

                return [
                    'status' => 'sent_to_microservice',
                    'task_id' => $response->json('task_id') ?? null,
                    'total_participants' => $totalN,
                ];
            } else {
                $errMsg = "Microservice returned status " . $response->status() . ": " . $response->body();
                $job->update([
                    'status' => 'failed',
                    'error_message' => $errMsg,
                    'completed_at' => now(),
                ]);

                return [
                    'status' => 'error',
                    'message' => $errMsg,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Failed connecting to IRT Microservice: " . $e->getMessage());

            $job->update([
                'status' => 'failed',
                'error_message' => "Connection failed to IRT Microservice: " . $e->getMessage(),
                'completed_at' => now(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
