<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\{
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
};
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Modules\Akademik\Models\LearningObjectiveTP;
use Modules\Akademik\Models\LearningOutcomeCP;

class TpGuruImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected LearningOutcomeCP $cp;
     protected int $cpUrut;

    public function __construct(LearningOutcomeCP $cp)
    {
        $this->cp = $cp;
        $this->cpUrut = LearningOutcomeCP::where('subjects_id', $cp->subjects_id)
            ->where('fase', $cp->fase)
            ->where('id', '<=', $cp->id)
            ->count();
    }

    /**
     * 🔒 VALIDASI WAJIB (INILAH YANG HILANG)
     */
    public function rules(): array
    {
        return [
            '*.nomor_tp'   => 'required',
            '*.rumusan_tp' => 'required|string',
            '*.urutan'     => 'nullable|integer',
            '*.is_active'  => 'nullable|boolean',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nomor_tp.required'   => 'Nomor TP wajib diisi',
            'rumusan_tp.required' => 'Rumusan TP wajib diisi',
            'urutan.integer'      => 'Urutan harus angka',
        ];
    }

    public function collection(Collection $rows)
    {
        $maxUrutan = (int) ($this->cp->tps()->max('urutan') ?? 0);

        foreach ($rows as $i => $row) {
            $line = $i + 2; // baris excel (header = 1)

            $nomorTp = trim((string) ($row['nomor_tp'] ?? ''));
            $rumusan = trim((string) ($row['rumusan_tp'] ?? ''));

            // 🚫 Lewati baris kosong / contoh
            if ($nomorTp === '' || $rumusan === '') {
                continue;
            }
            if (str_contains(strtolower($rumusan), 'contoh')) {
                continue;
            }

            // 🚫 Cegah duplikat nomor TP
            if ($this->cp->tps()->where('nomor_tp', $nomorTp)->exists()) {
                $this->onFailure(new Failure(
                    $line,
                    'nomor_tp',
                    ["Nomor TP {$nomorTp} sudah ada"],
                    $row->toArray()
                ));
                continue;
            }

            // 🔢 URUTAN
            if (isset($row['urutan']) && is_numeric($row['urutan'])) {
                $urutan = (int) $row['urutan'];
            } else {
                $urutan = ++$maxUrutan;
            }

            // 🧠 KODE TP — SAMA PERSIS DENGAN EDIT
            $kodeTp = sprintf(
                '%s.%s.%d.%s',
                strtoupper($this->cp->subject->code),
                strtoupper($this->cp->fase),
                $this->cpUrut,
                $nomorTp
            );

            LearningObjectiveTP::create([
                'cp_id'       => $this->cp->id,
                'kode_tp'     => $kodeTp,
                'nomor_tp'    => $nomorTp,
                'rumusan_tp'  => $rumusan,
                'urutan'      => $urutan,
                'is_active'   => isset($row['is_active'])
                    ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : true,
            ]);
        }
    }
    public function excollection(Collection $rows)
    {
        $nextUrutan = ($this->cp->tps()->max('urutan') ?? 0) + 1;
        foreach ($rows as $i => $row) {

            $nomorTp = trim((string) ($row['nomor_tp']));
            $rumusan = trim((string) ($row['rumusan_tp']));
            if ($nomorTp === '' || $rumusan === '') {
                // ❗ Kolom wajib kosong (seharusnya tidak terjadi karena validasi)
                continue;
            }
            if (str_contains(strtolower($rumusan), 'contoh')) {
                continue;
            }

            // ❗ DUPLIKAT → lempar error ke failures
            if ($this->cp->tps()->where('nomor_tp', $nomorTp)->exists()) {
                Validator::make([], [
                    'nomor_tp' => 'required',
                ], [
                    'required' => "Nomor TP {$nomorTp} sudah ada",
                ])->validate();
            }

            if (isset($row['urutan']) && is_numeric($row['urutan'])) {
                $urutan = (int) $row['urutan'];
            } else {
                $urutan = $nextUrutan++;
            }

            // CP urut (untuk kode)
            $cpUrut = LearningOutcomeCP::where('subjects_id', $this->cp->subjects_id)
                ->where('fase', $this->cp->fase)
                ->where('id', '<=', $this->cp->id)
                ->count();

            $kodeTp = strtoupper($this->cp->subject->code)
                .".{$this->cp->fase}.{$cpUrut}.{$nomorTp}";

            LearningObjectiveTP::create([
                'cp_id'       => $this->cp->id,
                'kode_tp'     => $kodeTp,
                'nomor_tp'    => $nomorTp,
                'rumusan_tp'  => $rumusan,
                'urutan'      => $urutan,
                'is_active'   => (bool) ($row['is_active'] ?? true),
            ]);
        }
    }
}