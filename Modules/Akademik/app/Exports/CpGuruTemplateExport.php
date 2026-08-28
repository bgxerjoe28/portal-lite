<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CpGuruTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [
            ['E', 'Contoh Judul CP', 'Contoh rumusan CP', 'koding, logika','baris ini tidak ikut di simpan mohon di hapus saja'],
        ];
    }

    public function headings(): array
    {
        return [
            'fase',         // E atau F
            'judul_cp',    // Judul CP
            'rumusan_cp',  // Rumusan CP
            'kata_kunci',  // Kata kunci, pisahkan dengan koma (,)
        ];
    }
}
