<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'NIS',
            'NISN',
            'Kelas',
            'Jenis Kelamin',
            'Agama',
            'No HP',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Alamat'
        ];
    }

    public function map($row): array
    {
        $gender = $row->gender ? 'L' : 'P';
        $classroom = $row->classrooms->first()->name ?? '-';

        return [
            $row->full_name,
            "'" . $row->nis,
            "'" . $row->nisn,
            $classroom,
            $gender,
            $row->religion?->name ?? '-',
            $row->phone,
            $row->birth_place,
            $row->birth_date,
            $row->address
        ];
    }
}
