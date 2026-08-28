<?php

namespace Modules\Cbt\Services;

use Modules\Cbt\Models\CbtBank;
use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Cbt\Models\CbtCttStudentResult;
use Modules\Cbt\Models\CbtCttItemAnalysis;
use Modules\Cbt\Models\CbtCttExamSummary;
use Illuminate\Support\Facades\DB;

class CttAnalyticsService
{
    /**
     * Calculate CTT score for a single student exam submission.
     */
    public function calculateStudentCtt(int $studentExamId): CbtCttStudentResult
    {
        $studentExam = CbtStudentExam::with('exam.bank.questions')->findOrFail($studentExamId);
        $questions = $studentExam->exam->bank->questions;
        $studentAnswers = CbtStudentAnswer::where('cbt_student_exam_id', $studentExamId)->get()->keyBy('cbt_question_id');

        $rawScore = 0.0;
        $maxScore = 0.0;
        $correctCount = 0;
        $wrongCount = 0;
        $unansweredCount = 0;

        foreach ($questions as $question) {
            $scoreMax = (float) $question->score;
            $maxScore += $scoreMax;

            $ans = $studentAnswers->get($question->id);
            if (!$ans || is_null($ans->selected_answer)) {
                $unansweredCount++;
                continue;
            }

            $pts = (float) ($ans->points_earned ?? 0.0);
            $rawScore += $pts;

            if ($ans->is_correct === true) {
                $correctCount++;
            } elseif ($ans->is_correct === false) {
                $wrongCount++;
            } else {
                // Untuk soal uraian / parsial
                if ($pts >= $scoreMax) {
                    $correctCount++;
                } elseif ($pts > 0) {
                    $correctCount++; // partial credit
                } else {
                    $wrongCount++;
                }
            }
        }

        $percentage = $maxScore > 0 ? min(100.0, max(0.0, ($rawScore / $maxScore) * 100)) : 0.0;

        return CbtCttStudentResult::updateOrCreate(
            ['cbt_student_exam_id' => $studentExamId],
            [
                'raw_score' => round($rawScore, 2),
                'max_score' => round($maxScore, 2),
                'percentage' => round($percentage, 2),
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'unanswered_count' => $unansweredCount,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Calculate CTT Exam-Wide Item Analysis & Cronbach's Alpha for an entire exam session.
     */
    public function calculateExamCtt(int $examId): array
    {
        $exam = CbtExam::with('bank.questions')->findOrFail($examId);
        $questions = $exam->bank->questions;

        // Ambil seluruh peserta yang sudah submit atau selesai
        $studentExams = CbtStudentExam::where('cbt_exam_id', $examId)
            ->whereIn('status', ['submitted', 'completed'])
            ->get();

        $totalN = $studentExams->count();

        if ($totalN === 0) {
            return [
                'status' => 'skipped',
                'message' => 'Belum ada peserta yang menyelesaikan ujian',
            ];
        }

        // 1. Hitung / Pastikan CTT Student Result ter-update & Ranking
        foreach ($studentExams as $stExam) {
            $this->calculateStudentCtt($stExam->id);
        }

        // Update Ranking
        /** @var \Illuminate\Database\Eloquent\Collection<int, CbtCttStudentResult> $results */
        $results = CbtCttStudentResult::whereIn('cbt_student_exam_id', $studentExams->pluck('id'))
            ->orderBy('raw_score', 'desc')
            ->get();

        $rank = 1;
        /** @var CbtCttStudentResult $res */
        foreach ($results as $res) {
            $res->update(['rank' => $rank++]);
        }

        // Matriks Jawaban: student_exam_id => [ question_id => binary_score (0/1) ]
        $studentScoresList = []; // total score per student
        $itemScoresMatrix = []; // question_id => [ student_exam_id => binary_score ]

        $answers = CbtStudentAnswer::whereIn('cbt_student_exam_id', $studentExams->pluck('id'))
            ->get()
            ->groupBy('cbt_student_exam_id');

        foreach ($results as $res) {
            $sId = $res->cbt_student_exam_id;
            $studentScoresList[$sId] = $res->raw_score;
            $stAnswers = $answers->get($sId) ? $answers->get($sId)->keyBy('cbt_question_id') : collect();

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id) ?? null;
                $isCorrectBiner = ($ans && $ans->is_correct === true) ? 1 : 0;
                $itemScoresMatrix[$q->id][$sId] = $isCorrectBiner;
            }
        }

        // 2. Analisis Butir Soal CTT (Tingkat Kesukaran p, Daya Beda D, Point Biserial)
        // Pengelompokan Kelompok Atas (Upper 27%) & Kelompok Bawah (Lower 27%)
        $nGroup = max(1, (int) round($totalN * 0.27));
        $sortedResults = $results->sortByDesc('raw_score')->values();
        $upperGroupIds = $sortedResults->take($nGroup)->pluck('cbt_student_exam_id')->toArray();
        $lowerGroupIds = $sortedResults->take(-$nGroup)->pluck('cbt_student_exam_id')->toArray();

        $meanTotal = count($studentScoresList) > 0 ? array_sum($studentScoresList) / count($studentScoresList) : 0;
        $stdTotal = $this->calculateStdDev(array_values($studentScoresList));

        $itemVarianceSum = 0.0;

        foreach ($questions as $q) {
            $qId = $q->id;
            $scores = $itemScoresMatrix[$qId] ?? [];
            $rCorrect = array_sum($scores);
            
            // Tingkat Kesukaran (p = R / N)
            $p = $totalN > 0 ? ($rCorrect / $totalN) : 0.0;

            // Daya Beda (D = P_Upper - P_Lower)
            $upperCorrect = 0;
            foreach ($upperGroupIds as $uid) {
                if (($scores[$uid] ?? 0) === 1) $upperCorrect++;
            }
            $lowerCorrect = 0;
            foreach ($lowerGroupIds as $lid) {
                if (($scores[$lid] ?? 0) === 1) $lowerCorrect++;
            }

            $pUpper = $nGroup > 0 ? ($upperCorrect / $nGroup) : 0.0;
            $pLower = $nGroup > 0 ? ($lowerCorrect / $nGroup) : 0.0;
            $D = $pUpper - $pLower;

            // Point Biserial Correlation (r_pbis)
            // r_pbis = ((M_p - M_total) / S_t) * sqrt(p / q)
            $mpScores = [];
            foreach ($scores as $sId => $scoreVal) {
                if ($scoreVal === 1) {
                    $mpScores[] = $studentScoresList[$sId] ?? 0;
                }
            }
            $meanP = count($mpScores) > 0 ? (array_sum($mpScores) / count($mpScores)) : 0;
            $qVal = 1 - $p;
            $rPbis = 0.0;
            if ($stdTotal > 0 && $p > 0 && $qVal > 0) {
                $rPbis = (($meanP - $meanTotal) / $stdTotal) * sqrt($p / $qVal);
                $rPbis = max(-1.0, min(1.0, $rPbis)); // Clamp -1 s/d 1
            }

            // Distractor Stats (Pengecoh CTT & IRT)
            $distractorStats = $this->analyzeDistractors(
                $q,
                $answers,
                $upperGroupIds,
                $lowerGroupIds,
                $studentScoresList,
                $meanTotal,
                $stdTotal
            );

            // Item Variance for Cronbach's Alpha
            $itemVarianceSum += ($p * $qVal);

            CbtCttItemAnalysis::updateOrCreate(
                [
                    'cbt_exam_id' => $examId,
                    'cbt_question_id' => $qId,
                ],
                [
                    'difficulty_index' => round($p, 4),
                    'discrimination_index' => round($D, 4),
                    'point_biserial' => round($rPbis, 4),
                    'distractor_stats' => $distractorStats,
                    'calculated_at' => now(),
                ]
            );
        }

        // 3. Cronbach's Alpha (Reliabilitas Soal)
        // alpha = (k / (k - 1)) * (1 - (sum(s_i^2) / s_t^2))
        $k = count($questions);
        $cronbachAlpha = 0.0;
        $varTotal = $stdTotal * $stdTotal;

        if ($k > 1 && $varTotal > 0) {
            $cronbachAlpha = ($k / ($k - 1)) * (1 - ($itemVarianceSum / $varTotal));
            $cronbachAlpha = max(0.0, min(1.0, $cronbachAlpha)); // Clamp 0 s/d 1
        }

        $scoresArray = array_values($studentScoresList);
        sort($scoresArray);

        $minScore = count($scoresArray) > 0 ? min($scoresArray) : 0;
        $maxScoreVal = count($scoresArray) > 0 ? max($scoresArray) : 0;
        $medianScore = count($scoresArray) > 0 ? $scoresArray[(int)floor(count($scoresArray) / 2)] : 0;

        CbtCttExamSummary::updateOrCreate(
            ['cbt_exam_id' => $examId],
            [
                'cronbach_alpha' => round($cronbachAlpha, 4),
                'mean_score' => round($meanTotal, 2),
                'median_score' => round($medianScore, 2),
                'std_deviation' => round($stdTotal, 2),
                'min_score' => round($minScore, 2),
                'max_score' => round($maxScoreVal, 2),
                'total_participants' => $totalN,
                'calculated_at' => now(),
            ]
        );

        return [
            'status' => 'completed',
            'total_participants' => $totalN,
            'cronbach_alpha' => round($cronbachAlpha, 4),
            'mean_score' => round($meanTotal, 2),
        ];
    }

    /**
     * Calculate CTT Item Analysis & Cronbach's Alpha across ALL exams using a Bank Soal.
     */
    public function calculateBankCtt(int $bankId): array
    {
        $bank = CbtBank::with('questions', 'exams')->findOrFail($bankId);
        $questions = $bank->questions;
        $examIds = $bank->exams->pluck('id')->toArray();

        if (empty($examIds)) {
            return [
                'status' => 'skipped',
                'message' => 'Bank soal ini belum digunakan di sesi ujian manapun',
            ];
        }

        $studentExams = CbtStudentExam::whereIn('cbt_exam_id', $examIds)
            ->whereIn('status', ['submitted', 'completed'])
            ->get();

        $totalN = $studentExams->count();

        if ($totalN === 0) {
            return [
                'status' => 'skipped',
                'message' => 'Belum ada peserta yang mengerjakan ujian dari bank soal ini',
            ];
        }

        foreach ($studentExams as $stExam) {
            $this->calculateStudentCtt($stExam->id);
        }

        $results = CbtCttStudentResult::whereIn('cbt_student_exam_id', $studentExams->pluck('id'))
            ->orderBy('raw_score', 'desc')
            ->get();

        $studentScoresList = [];
        $itemScoresMatrix = [];

        $answers = CbtStudentAnswer::whereIn('cbt_student_exam_id', $studentExams->pluck('id'))
            ->get()
            ->groupBy('cbt_student_exam_id');

        foreach ($results as $res) {
            $sId = $res->cbt_student_exam_id;
            $studentScoresList[$sId] = $res->raw_score;
            $stAnswers = $answers->get($sId) ? $answers->get($sId)->keyBy('cbt_question_id') : collect();

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id) ?? null;
                $isCorrectBiner = ($ans && $ans->is_correct === true) ? 1 : 0;
                $itemScoresMatrix[$q->id][$sId] = $isCorrectBiner;
            }
        }

        $nGroup = max(1, (int) round($totalN * 0.27));
        $sortedResults = $results->sortByDesc('raw_score')->values();
        $upperGroupIds = $sortedResults->take($nGroup)->pluck('cbt_student_exam_id')->toArray();
        $lowerGroupIds = $sortedResults->take(-$nGroup)->pluck('cbt_student_exam_id')->toArray();

        $meanTotal = count($studentScoresList) > 0 ? array_sum($studentScoresList) / count($studentScoresList) : 0;
        $stdTotal = $this->calculateStdDev(array_values($studentScoresList));
        $itemVarianceSum = 0.0;

        foreach ($questions as $q) {
            $qId = $q->id;
            $scores = $itemScoresMatrix[$qId] ?? [];
            $rCorrect = array_sum($scores);

            $p = $totalN > 0 ? ($rCorrect / $totalN) : 0.0;

            $upperCorrect = 0;
            foreach ($upperGroupIds as $uid) {
                if (($scores[$uid] ?? 0) === 1) $upperCorrect++;
            }
            $lowerCorrect = 0;
            foreach ($lowerGroupIds as $lid) {
                if (($scores[$lid] ?? 0) === 1) $lowerCorrect++;
            }

            $pUpper = $nGroup > 0 ? ($upperCorrect / $nGroup) : 0.0;
            $pLower = $nGroup > 0 ? ($lowerCorrect / $nGroup) : 0.0;
            $D = $pUpper - $pLower;

            $mpScores = [];
            foreach ($scores as $sId => $scoreVal) {
                if ($scoreVal === 1) {
                    $mpScores[] = $studentScoresList[$sId] ?? 0;
                }
            }
            $meanP = count($mpScores) > 0 ? (array_sum($mpScores) / count($mpScores)) : 0;
            $qVal = 1 - $p;
            $rPbis = 0.0;
            if ($stdTotal > 0 && $p > 0 && $qVal > 0) {
                $rPbis = (($meanP - $meanTotal) / $stdTotal) * sqrt($p / $qVal);
                $rPbis = max(-1.0, min(1.0, $rPbis));
            }

            $distractorStats = $this->analyzeDistractors(
                $q,
                $answers,
                $upperGroupIds,
                $lowerGroupIds,
                $studentScoresList,
                $meanTotal,
                $stdTotal
            );

            $itemVarianceSum += ($p * $qVal);

            CbtCttItemAnalysis::updateOrCreate(
                [
                    'cbt_bank_id' => $bankId,
                    'cbt_question_id' => $qId,
                ],
                [
                    'difficulty_index' => round($p, 4),
                    'discrimination_index' => round($D, 4),
                    'point_biserial' => round($rPbis, 4),
                    'distractor_stats' => $distractorStats,
                    'calculated_at' => now(),
                ]
            );
        }

        $k = count($questions);
        $cronbachAlpha = 0.0;
        $varTotal = $stdTotal * $stdTotal;

        if ($k > 1 && $varTotal > 0) {
            $cronbachAlpha = ($k / ($k - 1)) * (1 - ($itemVarianceSum / $varTotal));
            $cronbachAlpha = max(0.0, min(1.0, $cronbachAlpha));
        }

        $scoresArray = array_values($studentScoresList);
        sort($scoresArray);

        $minScore = count($scoresArray) > 0 ? min($scoresArray) : 0;
        $maxScoreVal = count($scoresArray) > 0 ? max($scoresArray) : 0;
        $medianScore = count($scoresArray) > 0 ? $scoresArray[(int)floor(count($scoresArray) / 2)] : 0;

        CbtCttExamSummary::updateOrCreate(
            ['cbt_bank_id' => $bankId],
            [
                'cronbach_alpha' => round($cronbachAlpha, 4),
                'mean_score' => round($meanTotal, 2),
                'median_score' => round($medianScore, 2),
                'std_deviation' => round($stdTotal, 2),
                'min_score' => round($minScore, 2),
                'max_score' => round($maxScoreVal, 2),
                'total_participants' => $totalN,
                'calculated_at' => now(),
            ]
        );

        return [
            'status' => 'completed',
            'total_participants' => $totalN,
            'cronbach_alpha' => round($cronbachAlpha, 4),
            'mean_score' => round($meanTotal, 2),
        ];
    }

    private function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count <= 1) return 0.0;

        $mean = array_sum($values) / $count;
        $variance = 0.0;
        foreach ($values as $val) {
            $variance += pow($val - $mean, 2);
        }

        return sqrt($variance / ($count - 1));
    }

    /**
     * Analyze distractors for multiple choice questions.
     *
     * @param \Illuminate\Support\Collection|array $groupedAnswers
     */
    private function analyzeDistractors(
        CbtQuestion $question,
        iterable $groupedAnswers,
        array $upperGroupIds,
        array $lowerGroupIds,
        array $studentScoresList,
        float $meanTotal,
        float $stdTotal
    ): array {
        if ($question->question_type !== 'pilihan_ganda') {
            return [];
        }

        $correctOption = is_array($question->correct_answer)
            ? strtoupper(trim($question->correct_answer[0] ?? ''))
            : strtoupper(trim((string)$question->correct_answer));

        $optionCounts = [];
        $upperCounts = [];
        $lowerCounts = [];
        $optionStudentScores = [];
        $totalResponses = 0;

        $nUpper = count($upperGroupIds);
        $nLower = count($lowerGroupIds);

        foreach ($groupedAnswers as $studentExamId => $stAnswers) {
            $ans = $stAnswers->keyBy('cbt_question_id')->get($question->id);
            if ($ans && !is_null($ans->selected_answer)) {
                $sel = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? '') : $ans->selected_answer;
                $optKey = strtoupper(trim((string)$sel));
                if ($optKey !== '') {
                    $optionCounts[$optKey] = ($optionCounts[$optKey] ?? 0) + 1;
                    $totalResponses++;

                    if (in_array($studentExamId, $upperGroupIds)) {
                        $upperCounts[$optKey] = ($upperCounts[$optKey] ?? 0) + 1;
                    }
                    if (in_array($studentExamId, $lowerGroupIds)) {
                        $lowerCounts[$optKey] = ($lowerCounts[$optKey] ?? 0) + 1;
                    }

                    $optionStudentScores[$optKey][] = $studentScoresList[$studentExamId] ?? 0;
                }
            }
        }

        $distractorStats = [];
        $allOptions = array_unique(array_merge(['A', 'B', 'C', 'D', 'E'], array_keys($optionCounts)));
        sort($allOptions);

        foreach ($allOptions as $opt) {
            $cnt = $optionCounts[$opt] ?? 0;
            $pct = $totalResponses > 0 ? round(($cnt / $totalResponses) * 100, 2) : 0.0;

            $uCnt = $upperCounts[$opt] ?? 0;
            $uPct = $nUpper > 0 ? round(($uCnt / $nUpper) * 100, 2) : 0.0;

            $lCnt = $lowerCounts[$opt] ?? 0;
            $lPct = $nLower > 0 ? round(($lCnt / $nLower) * 100, 2) : 0.0;

            $optScores = $optionStudentScores[$opt] ?? [];
            $meanOpt = count($optScores) > 0 ? (array_sum($optScores) / count($optScores)) : 0.0;
            $pOpt = $totalResponses > 0 ? ($cnt / $totalResponses) : 0.0;
            $qOpt = 1.0 - $pOpt;

            $rPbisOpt = 0.0;
            if ($stdTotal > 0 && $pOpt > 0 && $qOpt > 0) {
                $rPbisOpt = (($meanOpt - $meanTotal) / $stdTotal) * sqrt($pOpt / $qOpt);
                $rPbisOpt = max(-1.0, min(1.0, round($rPbisOpt, 4)));
            }

            $isKey = ($opt === $correctOption);
            $isEffective = false;
            $label = "";

            if ($isKey) {
                $label = "Kunci Jawaban";
                $isEffective = true;
            } else {
                if ($pct >= 5.0 && $lCnt > $uCnt && $rPbisOpt < 0) {
                    $isEffective = true;
                    $label = "Pengecoh Efektif";
                } elseif ($cnt === 0) {
                    $isEffective = false;
                    $label = "Pengecoh Tidak Dipilih (0%)";
                } elseif ($pct < 5.0) {
                    $isEffective = false;
                    $label = "Pengecoh Kurang Efektif (< 5%)";
                } elseif ($uCnt >= $lCnt) {
                    $isEffective = false;
                    $label = "Pengecoh Buruk (Lebih banyak dipilih kelompok atas)";
                } else {
                    $isEffective = false;
                    $label = "Pengecoh Kurang Efektif";
                }
            }

            $distractorStats[$opt] = [
                'option' => $opt,
                'is_key' => $isKey,
                'count' => $cnt,
                'percentage' => $pct,
                'upper_count' => $uCnt,
                'upper_percentage' => $uPct,
                'lower_count' => $lCnt,
                'lower_percentage' => $lPct,
                'point_biserial' => $rPbisOpt,
                'is_effective' => $isEffective,
                'status_label' => $label,
                'mean_theta' => null,
            ];
        }

        return $distractorStats;
    }
}
