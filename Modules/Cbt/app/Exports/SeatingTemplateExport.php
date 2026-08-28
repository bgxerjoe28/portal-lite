<?php

namespace Modules\Cbt\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SeatingTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'nisn',
            'kursi',
        ];
    }

    public function array(): array
    {
        return [
            [
                '1234567890',
                '1',
            ],
            [
                '0987654321',
                '2',
            ]
        ];
    }
}
