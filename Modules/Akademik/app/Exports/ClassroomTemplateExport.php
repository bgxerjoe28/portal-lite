<?php

namespace Modules\Akademik\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;

class ClassroomTemplateExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            // Jika ada data kelas, return datanya
            $classrooms = Classroom::with('teacher')->where('academic_year_id', $activeYear->id)->orderBy('level')->orderBy('name')->get();
            if ($classrooms->count() > 0) {
                return $classrooms;
            }
        }
        
        // Jika belum ada data, beri 1 baris dummy agar template tidak kosong
        return collect([
            (object)[
                'name' => 'X MIPA 1',
                'level' => '10',
                'major' => 'MIPA',
                'teacher' => null,
                'is_dummy' => true
            ]
        ]);
    }

    public function map($classroom): array
    {
        // Jika ini adalah baris dummy
        if (isset($classroom->is_dummy)) {
            return [
                $classroom->name,
                $classroom->level,
                $classroom->major,
                ''
            ];
        }

        return [
            $classroom->name,
            $classroom->level,
            $classroom->major,
            $classroom->teacher ? $classroom->teacher->nip : '',
        ];
    }

    public function headings(): array
    {
        return [
            'nama_kelas',     // Contoh: X IPA 1
            'tingkat',        // 10, 11, atau 12
            'jurusan',        // IPA, IPS, Bahasa, Umum
            'nip_wali_kelas', // NIP Guru (Wajib ada di data guru)
        ];
    }
}
