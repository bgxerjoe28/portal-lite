<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CpTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [
            ['INF', 'E', 'Contoh Judul CP', 'Contoh rumusan CP', 'koding, logika'],
        ];
    }

    public function headings(): array
    {
        return [
            'subject_code', // Kode Mata Pelajaran (harus ada di data mata pelajaran)
            'fase',         // E atau F
            'judul_cp',    // Judul CP
            'rumusan_cp',  // Rumusan CP
            'kata_kunci',  // Kata kunci, pisahkan dengan koma (,)
        ];
    }
}
