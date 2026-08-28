<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentTemplateExport implements ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return [
            'nama_lengkap',
            'email',            // Untuk Login
            'nis',
            'nisn',
            'jenis_kelamin',    // L atau P
            'agama',
            'no_hp',

        ];
    }
}
