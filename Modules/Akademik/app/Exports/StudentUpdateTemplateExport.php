<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentUpdateTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * Membuat heading/judul kolom sesuai dengan kebutuhan Import
     */
    public function headings(): array
    {
        return [
            'nisn',
            'birth_place',
            'birth_date',
            'address',
            'nama_ayah',
            'nama_ibu',
            'nama_wali',
        ];
    }

    /**
     * Memberikan satu baris contoh pengisian (Dummy Data)
     */
    public function array(): array
    {
        return [
            [
                '1234567890',
                'Semarang',
                '2008-05-17',
                'Jl. Kedungmundu No. 123, Semarang',
                'Nama Ayah Sesuai KTP',
                'Nama Ibu Sesuai KTP',
            ],
        ];
    }

    /**
     * Memberikan gaya pada heading (Bold) agar terlihat rapi
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
