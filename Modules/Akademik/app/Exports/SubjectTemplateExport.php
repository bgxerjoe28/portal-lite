<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubjectTemplateExport implements ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return [
            'nama_mapel',
            'kode',
            'is_religion',
        ];
    }
}
