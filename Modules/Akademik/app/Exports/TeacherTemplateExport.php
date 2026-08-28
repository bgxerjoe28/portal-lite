<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TeacherTemplateExport implements WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'nama_lengkap',
            'email',
            'nip',
            'jenis_kelamin_lp', // L atau P
            'no_hp',
            'gelar_belakang'
        ];
    }
}