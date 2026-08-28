<?php

namespace Modules\Akademik\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\StudentPermit;

class StudentPermitService
{
    public function getPermitListData($filters)
    {
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        return StudentPermit::with(['student.currentClassroom', 'recorder.teacher'])
            ->where('academic_year_id', $activeYearId)
            ->when($filters['student_id'] ?? null, function ($q, $studentId) {
                $q->where('student_id', $studentId);
            })
            ->when(!isset($filters['student_id']), function ($q) use ($filters) {
                $date = $filters['date'] ?? date('Y-m-d');
                $q->where('date', $date);
            })
            ->latest()
            ->get();
    }

    public function storePermit(array $data)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new \Exception('Tidak ada tahun ajaran aktif.');
        }

        $effectiveDays = $activeYear->effective_school_days;
        $startDate = Carbon::parse($data['date_range'][0]);
        $endDate = isset($data['date_range'][1]) ? Carbon::parse($data['date_range'][1]) : $startDate;

        $currentDate = $startDate->copy();
        $results = [];

        $studentIds = $data['student_ids'] ?? (isset($data['student_id']) ? [$data['student_id']] : []);

        foreach ($studentIds as $studentId) {
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dayName = strtolower($currentDate->format('D'));

                if (in_array($dayName, $effectiveDays)) {
                    $results[] = StudentPermit::updateOrCreate(
                        [
                            'date' => $currentDate->format('Y-m-d'),
                            'student_id' => $studentId,
                            'academic_year_id' => $activeYear->id,
                        ],
                        [
                            'permit_type' => $data['permit_type'],
                            'reason' => $data['reason'],
                            'start_slot' => $data['permit_type'] === 'D' ? ($data['start_slot'] ?? null) : null,
                            'end_slot' => $data['permit_type'] === 'D' ? ($data['end_slot'] ?? null) : null,
                            'teacher_id' => Auth::id(),
                        ]
                    );
                }
                $currentDate->addDay();
            }
        }

        return $results;
    }

    public function storeBulkPermits(array $data)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new \Exception('Tidak ada tahun ajaran aktif.');
        }

        $effectiveDays = $activeYear->effective_school_days;
        $startDate = Carbon::parse($data['date_range'][0]);
        $endDate = isset($data['date_range'][1]) ? Carbon::parse($data['date_range'][1]) : $startDate;

        $results = [];

        foreach ($data['rows'] as $row) {
            $studentId = $row['student_id'];
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $dayName = strtolower($currentDate->format('D'));

                if (in_array($dayName, $effectiveDays)) {
                    $results[] = StudentPermit::updateOrCreate(
                        [
                            'date' => $currentDate->format('Y-m-d'),
                            'student_id' => $studentId,
                            'academic_year_id' => $activeYear->id,
                        ],
                        [
                            'permit_type' => $row['permit_type'],
                            'reason' => $row['reason'] ?? null,
                            'start_slot' => $row['permit_type'] === 'D' ? ($row['start_slot'] ?? null) : null,
                            'end_slot' => $row['permit_type'] === 'D' ? ($row['end_slot'] ?? null) : null,
                            'teacher_id' => Auth::id(),
                        ]
                    );
                }
                $currentDate->addDay();
            }
        }

        return $results;
    }

    public function searchStudents($query)
    {
        return Student::with('currentClassroom')
            ->where('full_name', 'ilike', "%$query%")
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'full_name' => "{$s->full_name} | ".($s->currentClassroom->name ?? '-'),
            ]);
    }

    public function getTrashData()
    {
        return StudentPermit::onlyTrashed()
            ->with(['student.currentClassroom', 'recorder.teacher'])
            ->latest('deleted_at')
            ->get();
    }
    
    public function getStudentFrequency($filters = [])
    {
        return StudentPermit::query()
            ->selectRaw("
            student_id,
            COUNT(CASE WHEN permit_type = 'S' THEN 1 END) as total_sakit,
            COUNT(CASE WHEN permit_type = 'I' THEN 1 END) as total_izin,
            COUNT(CASE WHEN permit_type = 'D' THEN 1 END) as total_dispen,
            COUNT(CASE WHEN permit_type = 'A' THEN 1 END) as total_alfa,
            COUNT(CASE WHEN permit_type = 'T' THEN 1 END) as total_terlambat,
            COUNT(*) as total_akumulasi
        ")
            ->with(['student' => function ($q) {
                $q->select('id', 'full_name', 'nis', 'nisn')->with('currentClassroom');
            }])
            // Tambahkan filter rentang waktu jika diperlukan
            ->when($filters['start_date'] ?? null, fn ($q, $start) => $q->where('date', '>=', $start))
            ->when($filters['end_date'] ?? null, fn ($q, $end) => $q->where('date', '<=', $end))
            ->when(!empty($filters['academic_year_ids']), function($q) use ($filters) {
                // If it's a string (e.g. from query params like "1,2,3"), convert to array
                $ids = is_string($filters['academic_year_ids']) ? explode(',', $filters['academic_year_ids']) : $filters['academic_year_ids'];
                $q->whereIn('academic_year_id', $ids);
            })
            ->groupBy('student_id')
            ->orderBy('total_terlambat', 'desc') // Urutkan dari yang paling sering telat
            ->get();
    }
}
