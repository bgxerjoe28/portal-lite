<?php

namespace Modules\Akademik\Imports;

use Modules\Akademik\Models\LearningOutcomeCP;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\{
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsFailures
};
use Maatwebsite\Excel\Validators\Failure;

class CpGuruImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    protected int $subjectsId;

    public function __construct(int $subjectsId)
    {
        $this->subjectsId = $subjectsId;
    }

    /**
     * Proses data yang LOLOS validasi
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $fase  = strtoupper(trim($row['fase'] ?? ''));
            $judul = trim((string) ($row['judul_cp'] ?? ''));
            $rumus = trim((string) ($row['rumusan_cp'] ?? ''));

            // ⛔ Guard tambahan (aman banget)
            if ($fase === '' || $judul === '' || $rumus === '') {
                continue;
            }
            if (str_contains(strtolower($rumus), 'contoh')) {
                continue;
            }

            // Normalisasi kata kunci
            $kataKunci = null;
            if (!empty($row['kata_kunci'])) {
                $kataKunci = array_values(array_filter(
                    array_map('trim', explode(',', $row['kata_kunci']))
                ));
            }

            LearningOutcomeCP::updateOrCreate(
                [
                    'subjects_id' => $this->subjectsId,
                    'fase'       => $fase,
                    'judul_cp'   => $judul,
                ],
                [
                    'rumusan_cp' => $rumus,
                    'kata_kunci' => $kataKunci,
                ]
            );
        }
    }

    /**
     * VALIDASI PER BARIS (INI KUNCI UTAMA)
     */
    public function rules(): array
    {
        return [
            '*.fase' => ['required', Rule::in(['E', 'F'])],
            '*.judul_cp' => ['required', 'string', 'max:255'],
            '*.rumusan_cp' => ['required', 'string'],
            '*.kata_kunci' => ['nullable', 'string'],
        ];
    }

    /**
     * PESAN ERROR RAMAH GURU
     */
    public function customValidationMessages(): array
    {
        return [
            '*.fase.required' => 'Kolom fase wajib diisi',
            '*.fase.in' => 'Fase hanya boleh E atau F',
            '*.judul_cp.required' => 'Judul CP wajib diisi',
            '*.rumusan_cp.required' => 'Rumusan CP wajib diisi',
        ];
    }

    /**
     * Digunakan oleh Controller untuk ambil error
     */
    public function failures(): array
    {
        return $this->failures;
    }
}
