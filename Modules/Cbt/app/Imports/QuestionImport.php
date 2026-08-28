<?php

namespace Modules\Cbt\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Cbt\Models\CbtQuestion;
use Illuminate\Support\Str;

class QuestionImport implements ToCollection, WithHeadingRow
{
    protected int $cbtBankId;

    public function __construct(int $cbtBankId)
    {
        $this->cbtBankId = $cbtBankId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $questionType = strtolower(trim($row['tipe_soal'] ?? ''));
            $questionText = trim($row['pertanyaan'] ?? '');

            if (empty($questionType) || empty($questionText)) {
                continue;
            }

            // Normalisasi opsi
            $opsiA = isset($row['opsi_a']) ? trim($row['opsi_a']) : null;
            $opsiB = isset($row['opsi_b']) ? trim($row['opsi_b']) : null;
            $opsiC = isset($row['opsi_c']) ? trim($row['opsi_c']) : null;
            $opsiD = isset($row['opsi_d']) ? trim($row['opsi_d']) : null;
            $opsiE = isset($row['opsi_e']) ? trim($row['opsi_e']) : null;

            $options = null;
            $correctAnswer = null;
            $score = isset($row['bobot']) && is_numeric($row['bobot']) ? (float)$row['bobot'] : 1.00;

            $kunciRaw = isset($row['kunci_jawaban']) ? trim($row['kunci_jawaban']) : '';

            switch ($questionType) {
                case 'pilihan_ganda':
                case 'survey':
                    $options = array_filter([
                        'A' => $opsiA,
                        'B' => $opsiB,
                        'C' => $opsiC,
                        'D' => $opsiD,
                        'E' => $opsiE,
                    ]);
                    $correctAnswer = $questionType === 'survey' ? null : strtoupper($kunciRaw);
                    break;

                case 'isian_singkat':
                    $options = null;
                    // Bisa berupa alternatif jawaban yang dipisahkan koma
                    $correctAnswer = array_map(fn($item) => strtolower(trim($item)), explode(',', $kunciRaw));
                    break;

                case 'uraian':
                    $options = null;
                    $correctAnswer = empty($kunciRaw) ? null : [$kunciRaw];
                    break;

                case 'list':
                case 'checklist':
                    $options = array_filter([
                        'A' => $opsiA,
                        'B' => $opsiB,
                        'C' => $opsiC,
                        'D' => $opsiD,
                        'E' => $opsiE,
                    ]);
                    // Kunci dipisahkan koma (contoh: A,C)
                    $correctAnswer = array_map(fn($item) => strtoupper(trim($item)), explode(',', $kunciRaw));
                    break;

                case 'benar_salah':
                    // Opsi A s/d E adalah baris pernyataan
                    $statements = array_filter([
                        '1' => $opsiA,
                        '2' => $opsiB,
                        '3' => $opsiC,
                        '4' => $opsiD,
                        '5' => $opsiE,
                    ]);
                    $options = ['statements' => $statements];
                    
                    // Kunci adalah deretan B/S dipisahkan koma (contoh: B,S,S)
                    $answers = array_map(fn($item) => strtoupper(trim($item)), explode(',', $kunciRaw));
                    $correctMap = [];
                    foreach (array_keys($statements) as $idx => $key) {
                        $correctMap[$key] = isset($answers[$idx]) ? $answers[$idx] : 'S';
                    }
                    $correctAnswer = $correctMap;
                    break;

                case 'penjodohan':
                    // Premis sebelah kiri (Opsi A s/d E)
                    $premises = array_filter([
                        '1' => $opsiA,
                        '2' => $opsiB,
                        '3' => $opsiC,
                        '4' => $opsiD,
                        '5' => $opsiE,
                    ]);

                    // Target sebelah kanan (Target Penjodohan dipisahkan koma)
                    $targetsRaw = isset($row['target_penjodohan']) ? trim($row['target_penjodohan']) : '';
                    $targetsArr = array_map('trim', explode(',', $targetsRaw));
                    $targets = [];
                    foreach ($targetsArr as $idx => $t) {
                        if (!empty($t)) {
                            // Gunakan A, B, C... untuk target
                            $charKey = chr(65 + $idx); // A, B, C...
                            $targets[$charKey] = $t;
                        }
                    }

                    $options = [
                        'premises' => $premises,
                        'targets' => $targets
                    ];

                    // Kunci penjodohan dalam format 1-A,2-B,3-C
                    $matches = array_map('trim', explode(',', $kunciRaw));
                    $correctMap = [];
                    foreach ($matches as $match) {
                        $parts = explode('-', $match);
                        if (count($parts) === 2) {
                            $correctMap[trim($parts[0])] = strtoupper(trim($parts[1]));
                        }
                    }
                    $correctAnswer = $correctMap;
                    break;

                case 'skor_berbeda':
                    $options = array_filter([
                        'A' => $opsiA,
                        'B' => $opsiB,
                        'C' => $opsiC,
                        'D' => $opsiD,
                        'E' => $opsiE,
                    ]);
                    // Kunci berupa pemetaan skor A:5,B:3,C:0
                    $scores = array_map('trim', explode(',', $kunciRaw));
                    $correctMap = [];
                    foreach ($scores as $s) {
                        $parts = explode(':', $s);
                        if (count($parts) === 2) {
                            $correctMap[strtoupper(trim($parts[0]))] = (float)trim($parts[1]);
                        }
                    }
                    $correctAnswer = $correctMap;
                    break;

                case 'sorting':
                    // Item yang akan diurutkan
                    $items = array_filter([
                        '1' => $opsiA,
                        '2' => $opsiB,
                        '3' => $opsiC,
                        '4' => $opsiD,
                        '5' => $opsiE,
                    ]);
                    $options = ['items' => $items];
                    // Kunci adalah urutan index yang benar (contoh: 2,1,4,3)
                    $correctAnswer = array_map('trim', explode(',', $kunciRaw));
                    break;
            }

            CbtQuestion::create([
                'cbt_bank_id' => $this->cbtBankId,
                'question_type' => $questionType,
                'question_text' => $questionText,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'score' => $score,
            ]);
        }
    }
}
