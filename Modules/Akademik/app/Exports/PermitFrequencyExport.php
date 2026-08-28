<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PermitFrequencyExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Nama Siswa', 'NISN', 'NIS', 'Kelas', 'Sakit', 'Izin', 'Dispen', 'Terlambat', 'Alfa', 'Total'];
    }

    public function map($row): array
    {
        return [
            $row->student->full_name,
            "'".$row->student->nisn,
            "'".$row->student->nis,
            $row->student->currentClassroom->name ?? '-',
            $row->total_sakit,
            $row->total_izin,
            $row->total_dispen,
            $row->total_terlambat,
            $row->total_alfa,
            $row->total_akumulasi,
        ];
    }
}
