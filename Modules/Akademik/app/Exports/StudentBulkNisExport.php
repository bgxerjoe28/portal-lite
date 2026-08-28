<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentBulkNisExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->students as $student) {
            $classroom = $student->classrooms->first();
            $data[] = [
                $student->id,
                $student->full_name,
                $classroom ? $classroom->name : '-',
                $student->nis,
                $student->nisn,
            ];
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'ID SISWA (JANGAN DIUBAH)',
            'NAMA SISWA',
            'KELAS',
            'NIS',
            'NISN',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
