<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacilityAssetTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'kode_aset',
            'nama_aset',
            'kategori',
            'merk_tipe',
            'nomor_seri',
            'kondisi',
            'status',
            'lokasi_simpan',
            'catatan',
        ];
    }

    public function array(): array
    {
        return [
            [
                'AST-LAP-001',
                'Laptop ASUS ExpertBook Core i5',
                'Elektronik',
                'ASUS ExpertBook B1400',
                'SN-ASUS-982341',
                'good',
                'available',
                'Ruang Sarpras Lemari A',
                'Pengadaan BOS 2025, lengkap charger & tas',
            ],
            [
                'AST-PRJ-001',
                'Proyektor Epson EB-X500',
                'Multimedia',
                'Epson EB-X500',
                'SN-EPSON-44129',
                'good',
                'available',
                'Ruang Sarpras Rak B',
                'Lengkap kabel HDMI dan remote',
            ],
            [
                'AST-MIC-001',
                'Wireless Microphone Shure',
                'Musik',
                'Shure SVX288',
                'SN-SHURE-1102',
                'good',
                'available',
                'Ruang Audio & Sound',
                '2 unit mic wireless + receiver',
            ],
            [
                'AST-LAB-001',
                'Mikroskop Binokuler Olympus',
                'Laboratorium',
                'Olympus CX23',
                'SN-OLYMPUS-8821',
                'good',
                'available',
                'Lab Biologi Lemari 1',
                'Kondisi lensa bersih dan terawat',
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
