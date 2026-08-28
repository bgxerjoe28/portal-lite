<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\LearningOutcomeCP;
use Modules\Akademik\Models\Subject;

class CpImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {

            $line = $i + 2; // baris Excel (header = baris 1)

            $subjectCode = strtoupper(trim($row['subject_code'] ?? ''));
            $fase = strtoupper(trim($row['fase'] ?? ''));
            $judul = trim((string) ($row['judul_cp'] ?? ''));
            $rumusan = trim((string) ($row['rumusan_cp'] ?? ''));

            // --- VALIDASI WAJIB ---
            if ($subjectCode === '' || $fase === '' || $judul === '') {
                Log::warning("CP Import dilewati (baris $line): kolom wajib kosong");

                continue;
            }

            if (! in_array($fase, ['E', 'F'], true)) {
                Log::warning("CP Import dilewati (baris $line): fase tidak valid [$fase]");

                continue;
            }

            $subject = Subject::where('code', $subjectCode)->first();
            if (! $subject) {
                Log::warning("CP Import dilewati (baris $line): subject_code [$subjectCode] tidak ditemukan");

                continue;
            }

            // Kata kunci: teks → array
            $kataKunci = collect(explode(',', (string) ($row['kata_kunci'] ?? '')))
                ->map(fn ($v) => trim($v))
                ->filter()
                ->values()
                ->toArray();

            // --- SIMPAN (INI INTINYA) ---
            LearningOutcomeCP::updateOrCreate(
                [
                    'subjects_id' => $subject->id,
                    'fase' => $fase,
                    'judul_cp' => $judul,
                ],
                [
                    'rumusan_cp' => $rumusan,
                    'kata_kunci' => $kataKunci,
                ]
            );
        }
    }
}
