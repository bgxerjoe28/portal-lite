<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TpTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['1.1', 'Contoh rumusan TP', '1', '1'],
        ];
    }

    public function headings(): array
    {
        return [
            'nomor_tp',       // 1, 1.1, A, dll
            'rumusan_tp',     // Wajib
            'urutan',         // Opsional
            'is_active',      // 1 / 0
        ];
    }
}
