<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Cbt\Models\CbtAnalysisJob;
use Modules\Cbt\Models\CbtCttItemAnalysis;
use Modules\Cbt\Models\CbtIrtItemParameter;
use Modules\Cbt\Models\CbtIrtStudentAbility;

class CbtIrtCallbackController extends Controller
{
    /**
     * Handle incoming callback payload from Python IRT Microservice.
     *
     * Supports two modes:
     *  (A) result_path mode : microservice simpan JSON ke MinIO, callback hanya kirim path.
     *                         Laravel baca JSON dari MinIO disk s3_cbt_analytics.
     *  (B) inline mode      : data lengkap ada di body callback (fallback / backward compat).
     */
    public function handleCallback(Request $request): JsonResponse
    {
        // ── 1. Verifikasi Secret Key ──────────────────────────────────────────
        $secret         = $request->header('X-CBT-Secret');
        $expectedSecret = config('cbt.internal_secret', env('CBT_INTERNAL_SECRET'));

        if (!$secret || $secret !== $expectedSecret) {
            Log::warning('Unauthorized IRT Callback dari IP: ' . $request->ip());
            return response()->json(['message' => 'Unauthorized secret token'], 401);
        }

        // ── 2. Validasi Payload ───────────────────────────────────────────────
        $validated = $request->validate([
            'exam_id'       => 'nullable|integer',
            'bank_id'       => 'nullable|integer',
            'job_id'        => 'nullable|integer',
            'status'        => 'required|string|in:completed,failed',
            'model_type'    => 'nullable|string',
            'error_message' => 'nullable|string',

            // Mode A — hasil ada di MinIO
            'result_path'   => 'nullable|string',

            // Mode B — data inline (fallback)
            'item_parameters'                      => 'nullable|array',
            'item_parameters.*.question_id'        => 'required_with:item_parameters|integer',
            'item_parameters.*.difficulty_b'       => 'required_with:item_parameters|numeric',
            'item_parameters.*.discrimination_a'   => 'nullable|numeric',
            'item_parameters.*.guessing_c'         => 'nullable|numeric',
            'item_parameters.*.infit_mnsq'         => 'nullable|numeric',
            'item_parameters.*.outfit_mnsq'        => 'nullable|numeric',
            'student_abilities'                    => 'nullable|array',
            'student_abilities.*.student_exam_id'  => 'required_with:student_abilities|integer',
            'student_abilities.*.theta'            => 'required_with:student_abilities|numeric',
            'student_abilities.*.standard_error'   => 'nullable|numeric',
            'student_abilities.*.scaled_score'     => 'nullable|numeric',
            'student_abilities.*.percentile'       => 'nullable|numeric',
        ]);

        $examId    = $validated['exam_id'] ?? null;
        $bankId    = $validated['bank_id'] ?? null;
        $status    = $validated['status'];
        $modelType = $validated['model_type'] ?? '2PL';
        $resultPath = $validated['result_path'] ?? null;

        // ── 3. Temukan Job Tracking ───────────────────────────────────────────
        $job = null;
        if (!empty($validated['job_id'])) {
            $job = CbtAnalysisJob::find($validated['job_id']);
        }
        if (!$job) {
            $jobQuery = CbtAnalysisJob::where('job_type', 'LIKE', 'IRT_%');
            if ($examId) $jobQuery->where('cbt_exam_id', $examId);
            if ($bankId) $jobQuery->where('cbt_bank_id', $bankId);
            $job = $jobQuery->latest()->first();
        }

        // ── 4. Handle Gagal ───────────────────────────────────────────────────
        if ($status === 'failed') {
            if ($job) {
                $job->update([
                    'status'       => 'failed',
                    'error_message'=> $validated['error_message'] ?? 'Unknown error dari microservice',
                    'completed_at' => now(),
                ]);
            }
            Log::warning("IRT Callback status=failed untuk exam_id={$examId}, bank_id={$bankId}: " . ($validated['error_message'] ?? '-'));
            return response()->json(['message' => 'Callback failed logged']);
        }

        // ── 5. Muat Data Hasil (Mode A: MinIO atau Mode B: Inline) ───────────
        $itemParameters  = $validated['item_parameters'] ?? null;
        $studentAbilities = $validated['student_abilities'] ?? null;

        if ($resultPath && !$itemParameters && !$studentAbilities) {
            // Mode A — baca JSON dari MinIO
            try {
                $analyticsDisk = config('filesystems.analytics_disk', 's3_cbt_analytics');
                $disk = Storage::disk($analyticsDisk);
                if (!$disk->exists($resultPath)) {
                    Log::error("IRT Callback: result_path tidak ditemukan di MinIO: {$resultPath}");
                    return response()->json(['message' => "result_path tidak ditemukan: {$resultPath}"], 422);
                }

                $json = json_decode($disk->get($resultPath), true);
                if (!$json || json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("IRT Callback: Gagal parse JSON dari MinIO path: {$resultPath}");
                    return response()->json(['message' => 'Gagal membaca JSON dari MinIO'], 422);
                }

                $itemParameters   = $json['item_parameters'] ?? [];
                $studentAbilities = $json['student_abilities'] ?? [];
                $modelType        = $json['model_type'] ?? $modelType;

                Log::info("IRT Callback: Hasil dimuat dari MinIO — {$resultPath} (" . count($itemParameters) . " items, " . count($studentAbilities) . " students)");
            } catch (\Throwable $e) {
                Log::error("IRT Callback: Gagal baca dari MinIO: " . $e->getMessage());
                return response()->json(['message' => 'Gagal membaca hasil dari MinIO: ' . $e->getMessage()], 500);
            }
        }

        // ── 6. Simpan ke Database ─────────────────────────────────────────────
        DB::beginTransaction();
        try {
            // 6a. Simpan Parameter Butir Soal IRT (a, b, c)
            if (!empty($itemParameters)) {
                foreach ($itemParameters as $item) {
                    $keyMap = ['cbt_question_id' => $item['question_id']];
                    if ($examId) $keyMap['cbt_exam_id'] = $examId;
                    if ($bankId) $keyMap['cbt_bank_id'] = $bankId;

                    CbtIrtItemParameter::updateOrCreate(
                        $keyMap,
                        [
                            'model_type'       => $modelType,
                            'difficulty_b'     => round((float) $item['difficulty_b'], 4),
                            'discrimination_a' => round((float) ($item['discrimination_a'] ?? 1.0), 4),
                            'guessing_c'       => round((float) ($item['guessing_c'] ?? 0.0), 4),
                            'infit_mnsq'       => isset($item['infit_mnsq']) ? round((float) $item['infit_mnsq'], 4) : null,
                            'outfit_mnsq'      => isset($item['outfit_mnsq']) ? round((float) $item['outfit_mnsq'], 4) : null,
                            'calculated_at'    => now(),
                        ]
                    );
                }
            }

            // 6b. Simpan Kemampuan Siswa IRT (θ, SE, scaled_score, percentile)
            if (!empty($studentAbilities)) {
                foreach ($studentAbilities as $st) {
                    CbtIrtStudentAbility::updateOrCreate(
                        ['cbt_student_exam_id' => $st['student_exam_id']],
                        [
                            'theta'          => round((float) $st['theta'], 4),
                            'standard_error' => round((float) ($st['standard_error'] ?? 0.0), 4),
                            'scaled_score'   => round((float) ($st['scaled_score'] ?? 0.0), 2),
                            'percentile'     => isset($st['percentile']) ? round((float) $st['percentile'], 2) : null,
                            'calculated_at'  => now(),
                        ]
                    );
                }

                // 6c. Enrichment Distractor Stats dengan mean_theta per opsi (θ̄_k)
                $stExamIds    = array_column($studentAbilities, 'student_exam_id');
                $studentThetas = CbtIrtStudentAbility::whereIn('cbt_student_exam_id', $stExamIds)
                    ->pluck('theta', 'cbt_student_exam_id');

                if ($studentThetas->isNotEmpty()) {
                    $itemAnalysisQuery = CbtCttItemAnalysis::query();
                    if ($examId) $itemAnalysisQuery->where('cbt_exam_id', $examId);
                    if ($bankId) $itemAnalysisQuery->where('cbt_bank_id', $bankId);
                    $itemAnalyses = $itemAnalysisQuery->get();

                    $answersGrouped = \Modules\Cbt\Models\CbtStudentAnswer::whereIn('cbt_student_exam_id', $studentThetas->keys())
                        ->get()
                        ->groupBy('cbt_question_id');

                    foreach ($itemAnalyses as $itemAna) {
                        $stats = $itemAna->distractor_stats;
                        if (!is_array($stats) || empty($stats)) continue;

                        $qAnswers    = $answersGrouped->get($itemAna->cbt_question_id) ?? collect();
                        $optionThetas = [];

                        foreach ($qAnswers as $ans) {
                            if (is_null($ans->selected_answer)) continue;
                            $sel    = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? '') : $ans->selected_answer;
                            $optKey = strtoupper(trim((string) $sel));
                            $th     = $studentThetas->get($ans->cbt_student_exam_id);
                            if ($optKey !== '' && $th !== null) {
                                $optionThetas[$optKey][] = (float) $th;
                            }
                        }

                        foreach ($stats as $opt => &$optStat) {
                            $thetasOpt             = $optionThetas[$opt] ?? [];
                            $optStat['mean_theta'] = count($thetasOpt) > 0
                                ? round(array_sum($thetasOpt) / count($thetasOpt), 4)
                                : null;
                        }
                        unset($optStat);

                        $itemAna->update(['distractor_stats' => $stats]);
                    }
                }
            }

            // 6d. Update Job Status → completed
            if ($job) {
                $job->update([
                    'status'        => 'completed',
                    'progress_percent' => 100,
                    'completed_at'  => now(),
                    'error_message' => null,
                ]);
            }

            DB::commit();

            // 6e. Hapus file MinIO setelah berhasil tersimpan ke DB (cleanup)
            if ($resultPath) {
                try {
                    $analyticsDisk = config('filesystems.analytics_disk', 's3_cbt_analytics');
                    Storage::disk($analyticsDisk)->delete($resultPath);
                    Log::info("IRT Callback: File MinIO dihapus setelah disimpan ke DB — {$resultPath}");
                } catch (\Throwable $delErr) {
                    // Gagal hapus tidak fatal, hanya log
                    Log::warning("IRT Callback: Gagal hapus file MinIO {$resultPath}: " . $delErr->getMessage());
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Parameter IRT & kemampuan siswa berhasil disimpan.',
                'exam_id' => $examId,
                'bank_id' => $bankId,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('IRT Callback: Gagal simpan ke database — ' . $e->getMessage(), [
                'exam_id' => $examId,
                'bank_id' => $bankId,
                'trace'   => $e->getTraceAsString(),
            ]);

            if ($job) {
                $job->update([
                    'status'        => 'failed',
                    'error_message' => 'Database write error: ' . $e->getMessage(),
                    'completed_at'  => now(),
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
