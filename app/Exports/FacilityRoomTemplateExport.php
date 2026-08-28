<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacilityRoomTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'kode_ruangan',
            'nama_ruangan',
            'kapasitas',
            'lokasi',
            'fasilitas',
            'deskripsi',
            'status',
            'dapat_dipinjam',
        ];
    }

    public function array(): array
    {
        return [
            [
                'LAB-KOMP-1',
                'Lab Komputer 1',
                40,
                'Gedung B Lantai 2',
                'AC, Proyektor, Sound System, WiFi, 40 PC Siswa',
                'Laboratorium komputer untuk praktikum dan pelaksanaan ujian CBT',
                'available',
                1,
            ],
            [
                'AULA-UTAMA',
                'Aula Pertemuan Utama',
                250,
                'Gedung Utama Lantai 1',
                'AC Central, Panggung, Sound System, Proyektor Besar, Kursi Lipat',
                'Aula serbaguna untuk seminar, rapat pleno, dan acara sekolah',
                'available',
                1,
            ],
            [
                'LAB-FISIKA',
                'Laboratorium Fisika',
                36,
                'Gedung C Lantai 1',
                'Wastafel, Alat Praktikum Fisika, Meja Praktikum, Papan Tulis',
                'Laboratorium praktikum fisika untuk kegiatan belajar mengajar',
                'available',
                1,
            ],
            [
                'R-KELAS-X1',
                'Ruang Kelas X-1',
                36,
                'Gedung A Lantai 1',
                'Papan Tulis, Kipas Angin, Smart TV, Meja Kursi Siswa',
                'Ruang kelas reguler pembelajaran',
                'available',
                0,
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
