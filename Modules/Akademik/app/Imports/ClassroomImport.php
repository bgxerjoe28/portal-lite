<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Teacher;

class ClassroomImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // 1. Ambil Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (! $activeYear) {
            // Jika tidak ada tahun aktif, hentikan import (atau lemparkan error)
            throw new \Exception('Belum ada Tahun Ajaran yang aktif. Silakan set aktif dulu.');
        }

        foreach ($rows as $row) {
            if (! isset($row['nama_kelas'])) {
                continue;
            }

            DB::transaction(function () use ($row, $activeYear) {
                // 2. Cari Guru Berdasarkan NIP
                $teacherId = null;
                if (isset($row['nip_wali_kelas'])) {
                    $teacher = Teacher::where('nip', $row['nip_wali_kelas'])->first();
                    if ($teacher) {
                        $teacherId = $teacher->id;
                    }
                }

                // 3. Buat Kelas
                // Gunakan updateOrCreate agar kalau di-upload ulang tidak duplikat
                Classroom::updateOrCreate(
                    [
                        'academic_year_id' => $activeYear->id,
                        'name' => $row['nama_kelas'],
                    ],
                    [
                        'level' => $row['tingkat'] ?? 10,
                        'major' => $row['jurusan'] ?? '-',
                        'teacher_id' => $teacherId,
                    ]
                );
            });
        }
    }
}
