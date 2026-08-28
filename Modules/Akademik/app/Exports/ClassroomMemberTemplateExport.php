<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;

class ClassroomMemberTemplateExport implements ShouldAutoSize, WithHeadings, WithEvents
{
    public function headings(): array
    {
        return [
            'nisn',          // Kunci Utama pencarian siswa
            'nama_lengkap',  // Hanya sebagai referensi visual (tidak dipakai logic)
            'kelas',         // Nama Kelas (Harus persis dengan database, misal: X IPA 1)
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $activeYear = AcademicYear::where('is_active', true)->first();
                if ($activeYear) {
                    $classes = Classroom::where('academic_year_id', $activeYear->id)
                        ->orderBy('level')
                        ->orderBy('name')
                        ->pluck('name')
                        ->toArray();

                    if (!empty($classes)) {
                        // Tulis daftar kelas di kolom Z yang disembunyikan
                        // Ini untuk menghindari batas maksimal 255 karakter jika menggunakan formula string langsung
                        $row = 1;
                        foreach ($classes as $className) {
                            $event->sheet->getDelegate()->setCellValue('Z' . $row, $className);
                            $row++;
                        }
                        $event->sheet->getDelegate()->getColumnDimension('Z')->setVisible(false);

                        // Aplikasikan Dropdown (Data Validation) di kolom C baris 2 hingga 2000
                        $validation = $event->sheet->getDelegate()->getDataValidation('C2:C2000');
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(true);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Kelas Tidak Valid');
                        $validation->setError('Pilih kelas yang tersedia pada dropdown.');
                        $validation->setPromptTitle('Pilih Kelas');
                        $validation->setPrompt('Silakan pilih kelas dari daftar.');
                        // Referensi ke range data di kolom Z
                        $validation->setFormula1('=$Z$1:$Z$' . ($row - 1));
                    }
                }
            },
        ];
    }
}
