<?php

namespace Modules\Akademik\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use App\Models\User;

class AcademicYearRolloverService
{
    /**
     * Proses pindah semester (Ganjil ke Genap)
     * Siswa tetap di kelas yang sama. Wali kelas tetap.
     */
    public function processPindahSemester(AcademicYear $oldYear, AcademicYear $newYear)
    {
        try {
            DB::beginTransaction();

            // 1. Nonaktifkan tahun lama, aktifkan tahun baru
            $oldYear->update(['is_active' => false]);
            $newYear->update(['is_active' => true]);

            // 2. Ambil semua kelas di tahun lama
            $oldClassrooms = Classroom::where('academic_year_id', $oldYear->id)->get();

            foreach ($oldClassrooms as $oldClass) {
                // Duplikasi Kelas (Wali kelas ikut)
                $newClass = Classroom::firstOrCreate([
                    'academic_year_id' => $newYear->id,
                    'name'             => $oldClass->name,
                ], [
                    'teacher_id'       => $oldClass->teacher_id,
                    'level'            => $oldClass->level,
                    'major'            => $oldClass->major,
                ]);

                // 3. Ambil semua siswa di kelas lama yang aktif
                $oldClassroomStudents = ClassroomStudent::where('classroom_id', $oldClass->id)
                    ->where('academic_year_id', $oldYear->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->get();

                // Duplikasi ke kelas baru (Siswa otomatis masuk kelas)
                foreach ($oldClassroomStudents as $pivot) {
                    ClassroomStudent::firstOrCreate([
                        'academic_year_id' => $newYear->id,
                        'classroom_id'     => $newClass->id,
                        'student_id'       => $pivot->student_id,
                    ], [
                        'status'           => ClassroomStudent::STATUS_ACTIVE,
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Pindah Semester Gagal: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Proses ganti tahun ajaran baru (Genap ke Ganjil)
     * Kelas kosong (tanpa wali & siswa), proses naik/tinggal kelas, dan kelulusan.
     */
    public function processGantiTahunAjaran(AcademicYear $oldYear, AcademicYear $newYear, array $retainedStudentIds, array $graduatedStudentIds)
    {
        try {
            DB::beginTransaction();

            // 1. Nonaktifkan tahun lama, aktifkan tahun baru
            $oldYear->update(['is_active' => false]);
            $newYear->update(['is_active' => true]);

            // 2. Ambil semua kelas di tahun lama
            $oldClassrooms = Classroom::where('academic_year_id', $oldYear->id)->get();

            // Kumpulkan ID user untuk siswa yang lulus agar dinonaktifkan
            $usersToDeactivate = [];

            foreach ($oldClassrooms as $oldClass) {
                // 3. Duplikasi Kelas (TETAPI Wali Kelas Kosong)
                $newClass = Classroom::firstOrCreate([
                    'academic_year_id' => $newYear->id,
                    'name'             => $oldClass->name,
                ], [
                    'teacher_id'       => null, // Wali Kelas Kosong
                    'level'            => $oldClass->level,
                    'major'            => $oldClass->major,
                ]);

                // 4. Update status siswa di tahun lalu (Tidak ada duplikasi ke tahun baru)
                $oldClassroomStudents = ClassroomStudent::with('student')
                    ->where('classroom_id', $oldClass->id)
                    ->where('academic_year_id', $oldYear->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->get();

                foreach ($oldClassroomStudents as $pivot) {
                    if ($oldClass->level == 12) {
                        // KELAS 12: Lulus atau Tinggal
                        if (in_array($pivot->student_id, $retainedStudentIds)) {
                            // Tinggal Kelas
                            $pivot->update(['status' => ClassroomStudent::STATUS_RETAINED]);
                        } else {
                            // Lulus
                            $pivot->update(['status' => ClassroomStudent::STATUS_LULUS]);
                            if ($pivot->student && $pivot->student->user_id) {
                                $usersToDeactivate[] = $pivot->student->user_id;
                            }
                        }
                    } else {
                        // KELAS 10 & 11: Naik atau Tinggal
                        if (in_array($pivot->student_id, $retainedStudentIds)) {
                            // Tinggal Kelas
                            $pivot->update(['status' => ClassroomStudent::STATUS_RETAINED]);
                        } else {
                            // Naik Kelas
                            $pivot->update(['status' => ClassroomStudent::STATUS_PROMOTED]);
                        }
                    }
                    // Catatan: Tidak ada create ClassroomStudent baru di sini (Plotting Kosong)
                }
            }

            // 5. Nonaktifkan User Alumni
            if (!empty($usersToDeactivate)) {
                User::whereIn('id', $usersToDeactivate)->update(['is_active' => false]);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ganti Tahun Ajaran Gagal: ' . $e->getMessage());
            throw $e;
        }
    }
}
