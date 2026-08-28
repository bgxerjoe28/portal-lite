<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\LearningObjectiveTP;
use Modules\Akademik\Models\LearningOutcomeCP;
use Modules\Akademik\Models\Subject;

class TpImport implements ToCollection, WithHeadingRow
{
    protected LearningOutcomeCP $cp;

    public function __construct(LearningOutcomeCP $cp)
    {
        $this->cp = $cp;
    }

    public function collection(Collection $rows)
    {
        $nextUrutan = ($this->cp->tps()->max('urutan') ?? 0) + 1;
        foreach ($rows as $i => $row) {
            $line = $i + 2;


            $nomorTp = trim((string) ($row['nomor_tp'] ?? ''));
            $rumusan = trim((string) ($row['rumusan_tp'] ?? ''));


            if (! $nomorTp || ! $rumusan) {
                Log::warning("TP Import baris $line dilewati (kolom wajib kosong)");

                continue;
            }
            if (str_contains(strtolower($rumusan), 'contoh')) {
                continue;
            }
          
            // Cegah duplikat
            if ($this->cp->tps()->where('nomor_tp', $nomorTp)->exists()) {
                Log::warning("TP Import baris $line: nomor_tp $nomorTp sudah ada");

                continue;
            }

            // Urutan
             $urutan = isset($row['urutan']) && is_numeric($row['urutan'] && $row['urutan'] > 0)
                ? (int) $row['urutan']
                : $nextUrutan++;

            // CP urut (untuk kode)
            $cpUrut = LearningOutcomeCP::where('subjects_id', $this->cp->subjects_id)
                ->where('fase', $this->cp->fase)
                ->where('id', '<=', $this->cp->id)
                ->count();

            // Kode TP auto
            $kodeTp = strtoupper($this->cp->subject->code).".{$this->cp->fase}.{$cpUrut}.{$nomorTp}";
            LearningObjectiveTP::create([
                'cp_id' => $this->cp->id,
                'kode_tp' => $kodeTp,
                'nomor_tp' => $nomorTp,
                'rumusan_tp' => $rumusan,
                'urutan' => $urutan,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ]);
        }
    }
}
