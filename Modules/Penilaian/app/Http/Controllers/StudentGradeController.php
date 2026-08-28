<?php

namespace Modules\Penilaian\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Subject;
use Modules\Penilaian\Models\GradingComponent;
use Modules\Penilaian\Models\StudentGrade;

class StudentGradeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            abort(403, 'Profil siswa tidak ditemukan.');
        }

        // Ambil semua tahun ajaran untuk filter riwayat
        $academicYears = AcademicYear::orderBy('created_at', 'desc')->get();

        // Tentukan tahun ajaran aktif atau yang dipilih
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $request->input('academic_year_id')
            ? (int) $request->input('academic_year_id')
            : ($activeYear ? $activeYear->id : ($academicYears->first()?->id ?? null));

        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeYear;

        // Ambil kelas siswa pada tahun ajaran yang dipilih
        $classroomStudent = ClassroomStudent::with('classroom')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $selectedYearId)
            ->where('status', 'aktif')
            ->first();

        $classroom = $classroomStudent?->classroom;

        $subjectGrades = [];
        $overallStats = [
            'total_subjects' => 0,
            'completed_subjects' => 0,
            'gpa_avg' => 0,
        ];

        if ($classroom) {
            // Ambil jadwal pelajaran untuk kelas siswa pada tahun ajaran tersebut
            $schedules = Schedule::with(['subject', 'teacher'])
                ->where('classroom_id', $classroom->id)
                ->where('academic_year_id', $selectedYearId)
                ->when($student->religion_id, function ($q) use ($student) {
                    $q->where(function ($sub) use ($student) {
                        $sub->whereNull('religion_id')
                            ->orWhere('religion_id', $student->religion_id);
                    });
                })
                ->get();

            // Unique subjects by subject_id
            $uniqueSchedules = $schedules->unique('subject_id');

            $totalFinalScore = 0;
            $gradedCount = 0;

            foreach ($uniqueSchedules as $sch) {
                $subject = $sch->subject;
                if (!$subject) continue;

                // Ambil komponen penilaian untuk mapel & tahun ajaran ini
                $components = GradingComponent::with(['gradingItems' => function ($q) use ($classroom) {
                        $q->where('classroom_id', $classroom->id)
                          ->where('is_posted', true)
                          ->orderBy('date', 'asc');
                    }])
                    ->where('subject_id', $subject->id)
                    ->where('academic_year_id', $selectedYearId)
                    ->orderBy('sort_order', 'asc')
                    ->get();

                $filteredItemIds = $components->flatMap->gradingItems->pluck('id');

                $grades = StudentGrade::whereIn('grading_item_id', $filteredItemIds)
                    ->where('student_id', $student->id)
                    ->get();

                $componentDetails = [];
                $subjectTotalWeight = 0;
                $subjectWeightedSum = 0;
                $hasAnyGrade = false;

                foreach ($components as $comp) {
                    $itemScores = [];
                    foreach ($comp->gradingItems as $item) {
                        $gradeEntry = $grades->firstWhere('grading_item_id', $item->id);
                        $hasScore = ($gradeEntry !== null && $gradeEntry->score !== null);
                        if ($hasScore) {
                            $hasAnyGrade = true;
                        }
                        $itemScores[] = [
                            'item_id' => $item->id,
                            'title' => $item->title,
                            'date' => $item->date ? $item->date->format('Y-m-d') : null,
                            'score' => $hasScore ? (float) $gradeEntry->score : null,
                            'note' => $gradeEntry?->note,
                            'is_passed' => $hasScore ? ((float) $gradeEntry->score >= (float) $comp->passing_grade) : null,
                        ];
                    }

                    $filledScores = array_filter(array_column($itemScores, 'score'), fn($s) => $s !== null);
                    $avgScore = count($filledScores) > 0 ? (array_sum($filledScores) / count($filledScores)) : null;

                    if ($avgScore !== null) {
                        $subjectWeightedSum += ($avgScore * (float) $comp->weight);
                        $subjectTotalWeight += (float) $comp->weight;
                    }

                    $componentDetails[] = [
                        'id' => $comp->id,
                        'name' => $comp->name,
                        'weight' => (float) $comp->weight,
                        'passing_grade' => (float) $comp->passing_grade,
                        'items' => $itemScores,
                        'items_count' => count($itemScores),
                        'avg_score' => $avgScore !== null ? round($avgScore, 1) : null,
                        'is_passed' => $avgScore !== null ? ($avgScore >= (float) $comp->passing_grade) : null,
                    ];
                }

                $finalGrade = ($subjectTotalWeight > 0) ? round($subjectWeightedSum / $subjectTotalWeight, 1) : null;

                if ($finalGrade !== null) {
                    $totalFinalScore += $finalGrade;
                    $gradedCount++;
                }

                $subjectGrades[] = [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                    'subject_code' => $subject->code,
                    'teacher_name' => $sch->teacher?->name ?? '-',
                    'components' => $componentDetails,
                    'final_grade' => $finalGrade,
                    'has_grades' => $hasAnyGrade,
                ];
            }

            $overallStats['total_subjects'] = count($subjectGrades);
            $overallStats['completed_subjects'] = $gradedCount;
            $overallStats['gpa_avg'] = $gradedCount > 0 ? round($totalFinalScore / $gradedCount, 1) : 0;
        }

        return Inertia::render('Penilaian/Student/Index', [
            'student' => $student,
            'classroom' => $classroom,
            'academicYears' => $academicYears,
            'selectedYearId' => $selectedYearId,
            'selectedYear' => $selectedYear,
            'subjectGrades' => $subjectGrades,
            'overallStats' => $overallStats,
        ]);
    }
}
