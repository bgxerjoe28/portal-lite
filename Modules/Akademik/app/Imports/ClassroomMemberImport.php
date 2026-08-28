<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Student;

class ClassroomMemberImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // 1. Ambil Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new \Exception('Belum ada Tahun Ajaran yang aktif.');
        }

        // 2. Cache Data Kelas di Tahun Ini (Agar tidak query berulang-ulang)
        // Format Array: ['x ipa 1' => ID_1, 'x ipa 2' => ID_2]
        $classrooms = Classroom::where('academic_year_id', $activeYear->id)
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        foreach ($rows as $index => $row) {

            $nisn = trim($row['nisn'] ?? '');
            $namaKelas = strtolower(trim($row['kelas'] ?? ''));

            // Skip jika data tidak lengkap
            if (empty($nisn) || empty($namaKelas)) {
                continue;
            }

            DB::transaction(function () use ($nisn, $namaKelas, $classrooms, $activeYear, $index) {

                // A. Cari Siswa Berdasarkan NISN
                $student = Student::where('nisn', $nisn)->first();

                if (! $student) {
                    // Opsional: Throw error atau Skip. Kita skip saja tapi idealnya dicatat.
                    return;
                }

                // B. Cari ID Kelas dari Cache
                $classroomId = $classrooms[$namaKelas] ?? null;

                if (! $classroomId) {
                    // Jika nama kelas di Excel tidak ditemukan di Database
                    throw new \Exception('Baris '.($index + 2).": Kelas '$namaKelas' tidak ditemukan di Tahun Ajaran aktif.");
                }

                // C. Masukkan ke Pivot (Update jika sudah ada, Create jika belum)
                // Logic ini otomatis memindahkan siswa jika sebelumnya sudah ada di kelas lain pada tahun yang sama
                ClassroomStudent::updateOrCreate(
                    [
                        'academic_year_id' => $activeYear->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'classroom_id' => $classroomId,
                        'status' => 'aktif',
                    ]
                );
            });
        }
    }
}
