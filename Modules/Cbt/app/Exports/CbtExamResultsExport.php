<?php

namespace Modules\Cbt\Exports;

use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Akademik\Models\Student;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CbtExamResultsExport implements ShouldAutoSize, WithHeadings, FromArray, WithStyles
{
    protected $examId;

    public function __construct($examId)
    {
        $this->examId = $examId;
    }

    public function headings(): array
    {
        $exam = CbtExam::findOrFail($this->examId);
        $questions = CbtQuestion::where('cbt_bank_id', $exam->cbt_bank_id)
            ->orderBy('id')
            ->get();

        $headings = ['Nama', 'NISN', 'Kelas', 'Nilai'];
        foreach ($questions as $index => $q) {
            $type = $q->question_type;
            $correct = $q->correct_answer;

            $isSplit = false;
            if (is_array($correct) && !empty($correct)) {
                $keys = array_keys($correct);
                if ($keys !== range(0, count($correct) - 1)) {
                    $isSplit = true;
                }
            }

            if ($isSplit) {
                foreach ($correct as $subKey => $subVal) {
                    $keyText = is_array($subVal) ? implode(' / ', $subVal) : (string)$subVal;
                    $headings[] = "Soal " . ($index + 1) . " - " . $subKey . " (Kunci: " . $keyText . ")";
                }
            } else {
                $keyText = self::formatAnswerKey($type, $correct);
                $headings[] = "Soal " . ($index + 1) . " (Kunci: " . $keyText . ")";
            }
        }

        return $headings;
    }

    public function array(): array
    {
        $exam = CbtExam::with(['classrooms'])->findOrFail($this->examId);
        $questions = CbtQuestion::where('cbt_bank_id', $exam->cbt_bank_id)
            ->orderBy('id')
            ->get();

        $classroomIds = $exam->classrooms->pluck('id');

        $students = Student::whereHas('classrooms', function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            })
            ->with(['classrooms' => function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            }])
            ->orderBy('full_name')
            ->get();

        $studentExams = CbtStudentExam::where('cbt_exam_id', $this->examId)
            ->get()
            ->keyBy('student_id');

        $data = [];

        foreach ($students as $student) {
            $studentExam = $studentExams->get($student->id);
            $row = [
                $student->full_name,
                $student->nisn,
                $student->classrooms->first()->name ?? '-',
                $studentExam ? ($studentExam->score ?? '0') : '-',
            ];

            if ($studentExam) {
                $answers = CbtStudentAnswer::where('cbt_student_exam_id', $studentExam->id)
                    ->get()
                    ->keyBy('cbt_question_id');

                foreach ($questions as $q) {
                    $ans = $answers->get($q->id);
                    $selected = $ans ? $ans->selected_answer : null;

                    if (is_string($selected)) {
                        $decoded = json_decode($selected, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $selected = $decoded;
                        }
                    }

                    $type = $q->question_type;
                    $correct = $q->correct_answer;

                    $isSplit = false;
                    if (is_array($correct) && !empty($correct)) {
                        $keys = array_keys($correct);
                        if ($keys !== range(0, count($correct) - 1)) {
                            $isSplit = true;
                        }
                    }

                    if ($isSplit) {
                        foreach ($correct as $subKey => $subVal) {
                            if ($type === 'list' || $type === 'checklist') {
                                if (!$ans || is_null($ans->selected_answer)) {
                                    $row[] = '-';
                                } else {
                                    $selArr = is_array($selected) ? $selected : ($selected ? [$selected] : []);
                                    $selArr = array_map('strval', $selArr);
                                    $row[] = in_array((string)$subKey, $selArr) ? 'CHECK' : '-CHECK';
                                }
                            } else {
                                if (is_array($selected) && isset($selected[$subKey])) {
                                    $val = $selected[$subKey];
                                    $row[] = is_array($val) ? implode(', ', $val) : (string)$val;
                                } else {
                                    $row[] = '-';
                                }
                            }
                        }
                    } else {
                        // Normal question
                        if ($ans && $ans->selected_answer !== null && $ans->selected_answer !== '') {
                            $row[] = self::formatStudentAnswer($type, $ans->selected_answer);
                        } else {
                            $row[] = '-';
                        }
                    }
                }
            } else {
                // Siswa belum mulai ujian
                foreach ($questions as $q) {
                    $type = $q->question_type;
                    $correct = $q->correct_answer;

                    $isSplit = false;
                    if (is_array($correct) && !empty($correct)) {
                        $keys = array_keys($correct);
                        if ($keys !== range(0, count($correct) - 1)) {
                            $isSplit = true;
                        }
                    }

                    if ($isSplit) {
                        foreach ($correct as $subKey => $subVal) {
                            $row[] = '-';
                        }
                    } else {
                        $row[] = '-';
                    }
                }
            }

            $data[] = $row;
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($col = 5; $col <= $highestColumnIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            
            $headerValue = $sheet->getCell($colLetter . '1')->getValue();
            
            $correctKey = null;
            if (preg_match('/\(Kunci:\s*(.*?)\)/i', $headerValue, $matches)) {
                $correctKey = trim($matches[1]);
            }

            if ($correctKey === null) {
                continue;
            }

            for ($row = 2; $row <= $highestRow; $row++) {
                $cellCoordinate = $colLetter . $row;
                $cellValue = trim((string)$sheet->getCell($cellCoordinate)->getValue());

                if ($cellValue === '-' || $cellValue === '') {
                    continue;
                }

                $isCorrect = false;

                if (strcasecmp($cellValue, $correctKey) === 0) {
                    $isCorrect = true;
                } else {
                    $cleanVal = str_replace(' ', '', strtolower($cellValue));
                    $cleanKey = str_replace(' ', '', strtolower($correctKey));
                    if ($cleanVal === $cleanKey) {
                        $isCorrect = true;
                    } else {
                        $alternatives = explode('/', $cleanKey);
                        if (in_array($cleanVal, $alternatives)) {
                            $isCorrect = true;
                        }
                    }
                }

                $color = $isCorrect ? 'D1FAE5' : 'FEE2E2'; // Soft green and soft red
                $fontColor = $isCorrect ? '065F46' : '991B1B';

                $sheet->getStyle($cellCoordinate)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FF' . $color,
                        ],
                    ],
                    'font' => [
                        'color' => [
                            'argb' => 'FF' . $fontColor,
                        ],
                        'bold' => true,
                    ]
                ]);
            }
        }

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => [
                    'argb' => 'FFFFFFFF'
                ]
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF1E293B'
                ]
            ]
        ]);
    }

    public static function formatAnswerKey($type, $correctAnswer)
    {
        if (empty($correctAnswer)) {
            return '-';
        }
        
        if (!is_array($correctAnswer)) {
            return (string)$correctAnswer;
        }

        $flatArray = self::flattenArray($correctAnswer);
        
        if ($type === 'isian_singkat') {
            return implode(' / ', $flatArray);
        }
        if ($type === 'checklist' || $type === 'sorting') {
            return implode(', ', $flatArray);
        }
        if ($type === 'benar_salah') {
            $parts = [];
            foreach ($correctAnswer as $k => $v) {
                $vStr = is_array($v) ? implode(', ', self::flattenArray($v)) : (string)$v;
                $parts[] = "$k: $vStr";
            }
            return implode(' | ', $parts);
        }
        if ($type === 'penjodohan') {
            $parts = [];
            foreach ($correctAnswer as $k => $v) {
                $vStr = is_array($v) ? implode(', ', self::flattenArray($v)) : (string)$v;
                $parts[] = "$k-$vStr";
            }
            return implode(' | ', $parts);
        }
        
        return json_encode($correctAnswer);
    }

    public static function formatStudentAnswer($type, $selectedAnswer)
    {
        if (empty($selectedAnswer)) {
            return '-';
        }
        if (is_string($selectedAnswer)) {
            $decoded = json_decode($selectedAnswer, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $selectedAnswer = $decoded;
            }
        }
        
        if (!is_array($selectedAnswer)) {
            return (string)$selectedAnswer;
        }

        $flatArray = self::flattenArray($selectedAnswer);

        if ($type === 'isian_singkat') {
            return implode(', ', $flatArray);
        }
        if ($type === 'checklist' || $type === 'sorting') {
            return implode(', ', $flatArray);
        }
        if ($type === 'benar_salah') {
            $parts = [];
            foreach ($selectedAnswer as $k => $v) {
                $vStr = is_array($v) ? implode(', ', self::flattenArray($v)) : (string)$v;
                $parts[] = "$k: $vStr";
            }
            return implode(' | ', $parts);
        }
        if ($type === 'penjodohan') {
            $parts = [];
            foreach ($selectedAnswer as $k => $v) {
                $vStr = is_array($v) ? implode(', ', self::flattenArray($v)) : (string)$v;
                $parts[] = "$k-$vStr";
            }
            return implode(' | ', $parts);
        }
        
        return json_encode($selectedAnswer);
    }

    private static function flattenArray($array)
    {
        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $result = array_merge($result, self::flattenArray($item));
            } else {
                $result[] = (string)$item;
            }
        }
        return $result;
    }
}
