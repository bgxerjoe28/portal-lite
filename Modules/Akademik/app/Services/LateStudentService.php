<?php

namespace Modules\Akademik\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\StudentPermit;

class LateStudentService
{
    public function getTodayLates()
    {
        return StudentPermit::with(['student.currentClassroom'])
            ->where('date', now()->format('Y-m-d'))
            ->where('permit_type', 'T')
            ->latest()
            ->get();
    }

    public function storeLate(array $data)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $today = now()->format('Y-m-d');

        if (! $activeYear) {
            throw new \Exception('Tidak ada tahun ajaran aktif. Hubungi Admin.');
        }

        // Cek duplikasi
        $exists = StudentPermit::where('student_id', $data['student_id'])
            ->where('date', $today)
            ->where('permit_type', 'T')
            ->exists();

        if ($exists) {
            throw new \Exception('Siswa ini sudah tercatat terlambat hari ini.');
        }

        return StudentPermit::create([
            'student_id' => $data['student_id'],
            'teacher_id' => Auth::id(),
            'permit_type' => 'T',
            'date' => $today,
            'reason' => $data['reason'] ?? 'Terlambat masuk sekolah',
            'academic_year_id' => $activeYear->id,
        ]);
    }

    public function searchStudents(string $query)
    {
        $students = Student::with('currentClassroom')
            ->where('full_name', 'ilike', "%{$query}%")
            ->orWhere('nis', 'ilike', "%{$query}%")
            ->limit(10)
            ->get(['id', 'full_name', 'nis']);

        return $students->map(function ($s) {
            $kelas = $s->currentClassroom ? $s->currentClassroom->name : '-';

            return [
                'id' => $s->id,
                'full_name' => "{$s->full_name} | {$kelas}",
                'nis' => $s->nis,
            ];
        });
    }

    public function getLateDetail(int $id)
    {
        return StudentPermit::with(['student.classrooms'])->findOrFail($id);
    }

    public function generateLatePdf(int $id)
    {
        $late = StudentPermit::with(['student.currentClassroom'])->findOrFail($id);

        // Menggunakan view blade khusus PDF
        $pdf = Pdf::loadView('akademik::late.pdf_template', compact('late'))
            ->setPaper('a4', 'portrait');

        return $pdf;
    }
}
