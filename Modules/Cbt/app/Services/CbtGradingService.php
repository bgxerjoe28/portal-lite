<?php

namespace Modules\Cbt\Services;

use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Penilaian\Models\GradingItem;
use Modules\Penilaian\Models\StudentGrade;
use Modules\Akademik\Models\ClassroomStudent;
use Illuminate\Support\Facades\Log;

class CbtGradingService
{
    public static function gradeExam(int $studentExamId, ?string $submitType = null): float
    {
        $studentExam = CbtStudentExam::with('exam.bank.questions')->findOrFail($studentExamId);
        $questions = $studentExam->exam->bank->questions;
        $studentAnswers = CbtStudentAnswer::where('cbt_student_exam_id', $studentExamId)->get()->keyBy('cbt_question_id');

        // Jika tidak ada rekaman jawaban sama sekali di database (misal ujian diinput nilai langsung), pertahankan skor yang ada
        if ($studentAnswers->isEmpty() && !is_null($studentExam->score)) {
            return (float)$studentExam->score;
        }

        $totalMaxScore = 0;
        $totalPointsEarned = 0;

        foreach ($questions as $question) {
            $maxScore = (float)$question->score;
            $totalMaxScore += $maxScore;

            $studentAnswer = $studentAnswers->get($question->id);

            if (!$studentAnswer || is_null($studentAnswer->selected_answer)) {
                if ($studentAnswer) {
                    $studentAnswer->update([
                        'is_correct' => false,
                        'points_earned' => 0.00,
                    ]);
                }
                continue;
            }

            $selected = $studentAnswer->selected_answer;
            $correct = $question->correct_answer;
            $type = $question->question_type;

            $isCorrect = false;
            $pointsEarned = 0.00;

            switch ($type) {
                case 'pilihan_ganda':
                    // selected adalah string tunggal, misal: "A"
                    $selStr = is_array($selected) ? ($selected[0] ?? '') : $selected;
                    $corrStr = is_array($correct) ? ($correct[0] ?? '') : $correct;
                    
                    if (strtoupper(trim($selStr)) === strtoupper(trim($corrStr))) {
                        $isCorrect = true;
                        $pointsEarned = $maxScore;
                    }
                    break;

                case 'isian_singkat':
                    // Cek apakah kunci $correct adalah asosiatif (berarti ini multi-input)
                    $isMultiInput = false;
                    if (is_array($correct) && !empty($correct)) {
                        $keys = array_keys($correct);
                        if ($keys !== range(0, count($correct) - 1)) {
                            $isMultiInput = true;
                        }
                    }
                    if ($isMultiInput) {
                        $totalInputs = count($correct);
                        $correctCount = 0;
                        $selArr = is_array($selected) ? $selected : [];
                        
                        foreach ($correct as $subId => $corrAlts) {
                            $studentVal = strtolower(trim((string)($selArr[$subId] ?? '')));
                            $corrAltsList = is_array($corrAlts) ? $corrAlts : [$corrAlts];
                            $corrAltsList = array_map(fn($x) => strtolower(trim((string)$x)), $corrAltsList);
                            if (in_array($studentVal, $corrAltsList)) {
                                $correctCount++;
                            }
                        }
                        if ($totalInputs > 0) {
                            $pointsEarned = ($correctCount / $totalInputs) * $maxScore;
                            $isCorrect = ($correctCount === $totalInputs);
                        }
                    } else {
                        // Logika bawaan untuk input tunggal
                        $selClean = strtolower(trim(is_array($selected) ? implode(' ', $selected) : (string)$selected));
                        $corrList = is_array($correct) ? $correct : [$correct];
                        
                        // Pastikan tidak ada item array bersarang yang dikonversi langsung ke string
                        $flatCorrList = [];
                        foreach ($corrList as $item) {
                            if (is_array($item)) {
                                foreach ($item as $subItem) {
                                    $flatCorrList[] = strtolower(trim((string)$subItem));
                                }
                            } else {
                                $flatCorrList[] = strtolower(trim((string)$item));
                            }
                        }

                        if (in_array($selClean, $flatCorrList)) {
                            $isCorrect = true;
                            $pointsEarned = $maxScore;
                        }
                    }
                    break;

                case 'uraian':
                    // Cek apakah kunci $correct adalah asosiatif (soal berkolom / dynamic radio / multi-sub item)
                    $isAssoc = false;
                    if (is_array($correct) && !empty($correct)) {
                        $keys = array_keys($correct);
                        if ($keys !== range(0, count($correct) - 1)) {
                            $isAssoc = true;
                        }
                    }

                    if ($isAssoc) {
                        $totalItems = count($correct);
                        $correctCount = 0;
                        $selArr = is_array($selected) ? $selected : [];

                        foreach ($correct as $subId => $corrVal) {
                            $studentVal = strtoupper(trim((string)($selArr[$subId] ?? '')));
                            if (is_array($corrVal)) {
                                $corrList = array_map(fn($x) => strtoupper(trim((string)$x)), $corrVal);
                                if ($studentVal !== '' && in_array($studentVal, $corrList)) {
                                    $correctCount++;
                                }
                            } else {
                                $targetVal = strtoupper(trim((string)$corrVal));
                                if ($targetVal !== '' && $studentVal === $targetVal) {
                                    $correctCount++;
                                }
                            }
                        }

                        if ($totalItems > 0 && $correctCount > 0) {
                            $pointsEarned = ($correctCount / $totalItems) * $maxScore;
                            $isCorrect = ($correctCount === $totalItems);
                        } elseif (isset($studentAnswer->points_earned) && (float)$studentAnswer->points_earned > 0) {
                            // Pertahankan nilai jika sudah dinilai manual oleh guru
                            $pointsEarned = (float)$studentAnswer->points_earned;
                            $isCorrect = ($pointsEarned >= $maxScore);
                        } else {
                            $pointsEarned = 0.00;
                            $isCorrect = false;
                        }
                    } else {
                        // Uraian manual murni, dinilai manual oleh guru
                        $isCorrect = null;
                        $pointsEarned = (float)($studentAnswer->points_earned ?? 0.00);
                    }
                    break;

                case 'list':
                case 'checklist':
                    // Jika correct adalah array asosiatif (misal {"10001": "CHECK", "10002": "-CHECK"})
                    $isCorrectMap = false;
                    if (is_array($correct) && !empty($correct)) {
                        $keys = array_keys($correct);
                        if ($keys !== range(0, count($correct) - 1)) {
                            $isCorrectMap = true;
                        }
                    }
                    if ($isCorrectMap) {
                        $totalItems = count($correct);
                        $correctCount = 0;
                        
                        $selArr = is_array($selected) ? $selected : [$selected];
                        $selArr = array_map('strval', $selArr);
                        
                        foreach ($correct as $id => $expected) {
                            $isStudentChecked = in_array((string)$id, $selArr);
                            $isExpectedChecked = (strtoupper(trim($expected)) === 'CHECK');
                            
                            if ($isStudentChecked === $isExpectedChecked) {
                                $correctCount++;
                            }
                        }
                        
                        if ($totalItems > 0) {
                            $pointsEarned = ($correctCount / $totalItems) * $maxScore;
                            $isCorrect = ($correctCount === $totalItems);
                        }
                    } else {
                        // Logika bawaan jika array biasa
                        $selArr = is_array($selected) ? $selected : [$selected];
                        $corrArr = is_array($correct) ? $correct : [$correct];

                        $selArr = array_map('strtoupper', array_map('trim', $selArr));
                        $corrArr = array_map('strtoupper', array_map('trim', $corrArr));

                        sort($selArr);
                        sort($corrArr);

                        if ($selArr === $corrArr) {
                            $isCorrect = true;
                            $pointsEarned = $maxScore;
                        }
                    }
                    break;

                case 'benar_salah':
                    // options['statements'] adalah map {"1": "Pernyataan 1", "2": "Pernyataan 2"}
                    // correct adalah map kunci {"1": "B", "2": "S", "3": "B"}
                    // selected adalah map jawaban siswa {"1": "B", "2": "S", "3": "S"}
                    $statements = $question->options['statements'] ?? [];
                    $totalStatements = count($statements);

                    if ($totalStatements > 0) {
                        $correctCount = 0;
                        foreach ($statements as $key => $stmt) {
                            $selVal = strtoupper(trim((string)($selected[$key] ?? '')));
                            $corrVal = strtoupper(trim((string)($correct[$key] ?? '')));
                            if ($selVal === $corrVal) {
                                $correctCount++;
                            }
                        }
                        $pointsEarned = ($correctCount / $totalStatements) * $maxScore;
                        $isCorrect = ($correctCount === $totalStatements);
                    }
                    break;

                case 'penjodohan':
                    // options['premises'] adalah map {"1": "Premis 1", "2": "Premis 2"}
                    // correct adalah map pemetaan {"1": "A", "2": "B"}
                    // selected adalah map jawaban siswa {"1": "A", "2": "B"}
                    $premises = $question->options['premises'] ?? [];
                    $totalPremises = count($premises);

                    if ($totalPremises > 0) {
                        $correctCount = 0;
                        foreach ($premises as $key => $prem) {
                            $selVal = strtoupper(trim((string)($selected[$key] ?? '')));
                            $corrVal = strtoupper(trim((string)($correct[$key] ?? '')));
                            if ($selVal === $corrVal) {
                                $correctCount++;
                            }
                        }
                        $pointsEarned = ($correctCount / $totalPremises) * $maxScore;
                        $isCorrect = ($correctCount === $totalPremises);
                    }
                    break;

                case 'survey':
                    // Survey selalu dianggap benar/berhasil, skor konstan
                    $isCorrect = true;
                    $pointsEarned = $maxScore;
                    break;

                case 'skor_berbeda':
                    // selected adalah string pilihan tunggal, misal: "A"
                    // correct adalah map skor, misal: {"A": 5.00, "B": 3.00, "C": 0.00}
                    $selKey = strtoupper(trim(is_array($selected) ? ($selected[0] ?? '') : $selected));
                    $pointsEarned = (float)($correct[$selKey] ?? 0.00);
                    // Karena nilainya dinamis, isCorrect = true jika poin > 0
                    $isCorrect = ($pointsEarned > 0);
                    break;

                case 'sorting':
                    // correct adalah array urutan benar, misal: ["2", "1", "4", "3"]
                    // selected adalah array urutan siswa, misal: ["2", "1", "4", "3"]
                    $selArr = is_array($selected) ? array_values($selected) : [$selected];
                    $corrArr = is_array($correct) ? array_values($correct) : [$correct];

                    $selArr = array_map('strval', $selArr);
                    $corrArr = array_map('strval', $corrArr);

                    if ($selArr === $corrArr) {
                        $isCorrect = true;
                        $pointsEarned = $maxScore;
                    }
                    break;
            }

            $studentAnswer->update([
                'is_correct' => $isCorrect,
                'points_earned' => round($pointsEarned, 2),
            ]);

            $totalPointsEarned += $pointsEarned;
        }

        // Hitung nilai akhir skala 0-100
        $finalScore = 0.00;
        if ($totalMaxScore > 0) {
            $finalScore = ($totalPointsEarned / $totalMaxScore) * 100;
        }

        $updateData = [
            'score' => round($finalScore, 2),
            'submitted_at' => now(),
            'status' => 'submitted',
        ];

        if ($submitType !== null) {
            $updateData['submit_type'] = $submitType;
        } elseif (!$studentExam->submit_type) {
            $updateData['submit_type'] = 'student';
        }

        $studentExam->update($updateData);

        // Auto-sync ke Modul Penilaian (StudentGrade) jika ujian terhubung ke Kategori Nilai
        try {
            $exam = $studentExam->exam;
            if ($exam && $exam->grading_component_id) {
                $classroomStudent = ClassroomStudent::where('student_id', $studentExam->student_id)
                    ->where('status', 'aktif')
                    ->first();

                if ($classroomStudent) {
                    $gradingItem = GradingItem::firstOrCreate(
                        [
                            'grading_component_id' => $exam->grading_component_id,
                            'classroom_id' => $classroomStudent->classroom_id,
                            'title' => $exam->title,
                        ],
                        [
                            'date' => \Carbon\Carbon::parse($exam->start_time)->toDateString(),
                        ]
                    );

                    StudentGrade::updateOrCreate(
                        [
                            'grading_item_id' => $gradingItem->id,
                            'student_id' => $studentExam->student_id,
                        ],
                        [
                            'score' => round($finalScore, 2),
                            'note' => 'CBT: ' . $exam->title,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal auto-sync nilai CBT ke modul Penilaian: ' . $e->getMessage());
        }

        return round($finalScore, 2);
    }
}
