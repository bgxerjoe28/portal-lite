<?php

namespace Modules\Penilaian\Services;

use App\Services\TeacherService;
use Illuminate\Support\Facades\DB;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Subject;
use Modules\Akademik\Models\Teacher;
use Modules\Penilaian\Models\GradingComponent;
use Modules\Penilaian\Models\GradingItem;
use Modules\Penilaian\Models\StudentGrade;

class GradeService
{
    /**
     * Menghasilkan Matriks Rekap Nilai yang Akurat (Anti-Bocor)
     */
    public function getRecapData(int $classId, int $subjectId, int $activeYearId)
    {
        $teacherId = TeacherService::getAuthTeacherId();

        $user = auth()->user();

        // 1. Ambil Jadwal & Komponen (Filter Kelas XI 1 sesuai gambar)
        $scheduleQuery = Schedule::with('religion')
            ->where('academic_year_id', $activeYearId)
            ->where('classroom_id', $classId)
            ->where('subject_id', $subjectId);

        if ($user && !$user->hasRole('admin') && $teacherId) {
            $scheduleQuery->where('teacher_id', $teacherId);
        }

        $schedule = $scheduleQuery->first();
        $targetTeacherId = $schedule?->teacher_id ?? $teacherId;

        $componentsQuery = GradingComponent::where('subject_id', $subjectId)
            ->where('academic_year_id', $activeYearId);

        if ($targetTeacherId) {
            $componentsQuery->where(function ($q) use ($targetTeacherId) {
                $q->where('teacher_id', $targetTeacherId)
                  ->orWhereNull('teacher_id');
            });
        }

        $components = $componentsQuery
            ->with(['gradingItems' => function ($q) use ($classId) {
                // 🛡️ Menghindari kebocoran data dari kelas lain
                $q->where('classroom_id', $classId)->orderBy('date', 'asc');
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        $filteredItemIds = $components->flatMap->gradingItems->pluck('id');

        // 2. Ambil Siswa Aktif (Filter Agama jika diperlukan)
        $students = Student::whereHas('classrooms', function ($q) use ($classId, $activeYearId) {
            $q->where('classroom_students.classroom_id', $classId)
                ->where('classroom_students.academic_year_id', $activeYearId)
                ->where('classroom_students.status', 'aktif');
        })
            ->when($schedule && $schedule->religion_id, fn ($q) => $q->where('religion_id', $schedule->religion_id))
            ->orderBy('full_name', 'asc')
            ->get();

        $grades = StudentGrade::whereIn('grading_item_id', $filteredItemIds)->get();

        // 3. Mapping Matriks Nilai
        $recapData = $students->map(function ($student) use ($components, $grades) {
            $categoryScores = [];
            $overallTotal = 0;
            $totalWeight = 0;

            foreach ($components as $comp) {
                $itemScores = [];
                $postedScores = [];
                foreach ($comp->gradingItems as $item) {
                    $scoreEntry = $grades->where('student_id', $student->id)
                        ->where('grading_item_id', $item->id)
                        ->first();
                    $val = $scoreEntry ? (float)$scoreEntry->score : 0;
                    $itemScores['item_'.$item->id] = $val;

                    // Hanya item yang berstatus is_posted == true yang masuk ke perhitungan rerata
                    if ($item->is_posted) {
                        $postedScores[] = $val;
                    }
                }

                $avgComp = count($postedScores) > 0 ? array_sum($postedScores) / count($postedScores) : 0;
                $categoryScores[$comp->id] = [
                    'items' => $itemScores,
                    'avg' => $avgComp,
                ];

                $overallTotal += ($avgComp * $comp->weight);
                $totalWeight += $comp->weight;
            }

            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'categories' => $categoryScores,
                'final_grade' => $totalWeight > 0 ? $overallTotal / $totalWeight : 0,
            ];
        });

        $subject = Subject::find($subjectId);
        if ($subject && $schedule && $schedule->religion) {
            $subject = clone $subject;
            if (!str_contains($subject->name, '(' . $schedule->religion->name . ')')) {
                $subject->name .= ' (' . $schedule->religion->name . ')';
            }
        }

        return [
            'components' => $components,
            'recapData' => $recapData,
            'subject' => $subject,
            'classroom' => Classroom::find($classId),
            'teacher' => Teacher::find($teacherId), // Pastikan ini objek profil guru
        ];
    }

    public function getGradeListData(array $filters)
    {
        $teacherId = TeacherService::getAuthTeacherId();

        // Ambil semua tahun ajaran untuk dropdown
        $academicYears = AcademicYear::orderBy('created_at', 'desc')->get();

        // Gunakan filter academic_year_id jika ada, default ke tahun aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = ! empty($filters['academic_year_id'])
            ? (int) $filters['academic_year_id']
            : ($activeYear ? $activeYear->id : null);

        // 1. Ambil Dropdown Mapel & Kelas (Filter oleh guru & tahun ajaran yang dipilih)
        $user = auth()->user();
        $scheduleQuery = Schedule::with(['subject', 'classroom', 'religion'])
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId));

        if ($user && !$user->hasRole('admin') && $teacherId) {
            $scheduleQuery->where('teacher_id', $teacherId);
        }

        $schedules = $scheduleQuery->get();

        $schedules->each(function ($schedule) {
            if ($schedule->subject && $schedule->religion) {
                $clone = clone $schedule->subject;
                if (!str_contains($clone->name, '(' . $schedule->religion->name . ')')) {
                    $clone->name .= ' (' . $schedule->religion->name . ')';
                }
                $schedule->setRelation('subject', $clone);
            }
        });

        $subjects = $schedules->pluck('subject')->filter()->unique('id')->values();
        $classrooms = $schedules->pluck('classroom')->filter()->unique('id')->values();

        // 2. Ambil Item Penilaian (Filter Guru, Tahun Ajaran & Request)
        $items = GradingItem::with(['component.subject', 'classroom'])
            ->whereHas('component', function ($q) use ($teacherId, $filters, $selectedYearId, $user) {
                if ($user && !$user->hasRole('admin') && $teacherId) {
                    $q->where('teacher_id', $teacherId);
                }
                if ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                }
                if (! empty($filters['subject_id'])) {
                    $q->where('subject_id', $filters['subject_id']);
                }
            })
            ->when(! empty($filters['classroom_id']), function ($query) use ($filters) {
                $query->where('classroom_id', $filters['classroom_id']);
            })
            ->latest()
            ->get();

        return compact('subjects', 'classrooms', 'items', 'activeYear', 'academicYears', 'selectedYearId', 'schedules');
    }

    public function storeGrade(array $data)
    {
        return DB::transaction(function () use ($data) {
            $item = GradingItem::create([
                'grading_component_id' => $data['grading_component_id'],
                'classroom_id' => $data['classroom_id'],
                'title' => $data['title'],
                'date' => $data['date'],
                'is_posted' => isset($data['is_posted']) ? (bool)$data['is_posted'] : true,
            ]);

            foreach ($data['scores'] as $score) {
                StudentGrade::create([
                    'grading_item_id' => $item->id,
                    'student_id' => $score['student_id'],
                    'score' => $score['score'],
                    'note' => $score['note'] ?? null,
                ]);
            }

            return $item;
        });
    }

    /**
     * Update Item Penilaian & Nilai Siswa
     */
    public function updateGrade(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $item = GradingItem::findOrFail($id);
            $updatePayload = [
                'title' => $data['title'],
                'date' => $data['date'],
                'grading_component_id' => $data['grading_component_id'],
            ];
            if (isset($data['is_posted'])) {
                $updatePayload['is_posted'] = (bool)$data['is_posted'];
            }
            $item->update($updatePayload);

            foreach ($data['scores'] as $score) {
                StudentGrade::updateOrCreate(
                    ['grading_item_id' => $item->id, 'student_id' => $score['student_id']],
                    ['score' => $score['score'], 'note' => $score['note'] ?? null]
                );
            }

            return $item;
        });
    }

    public function getCreateData(array $filters)
    {
        $teacherId = TeacherService::getAuthTeacherId();
        $activeYear = AcademicYear::where('is_active', true)->first();

        // 1. Ambil Komponen Penilaian untuk Mapel tersebut
        $components = GradingComponent::where('subject_id', $filters['subject_id'])
            ->where('academic_year_id', $activeYear->id)
            ->where('teacher_id', $teacherId)
            ->get();

        // 2. Ambil Siswa di kelas tersebut (Filter Agama jika ada jadwal spesifik)
        $schedule = Schedule::with('religion')
            ->where('classroom_id', $filters['classroom_id'])
            ->where('subject_id', $filters['subject_id'])
            ->where('teacher_id', $teacherId)
            ->first();

        $students = Student::whereHas('classrooms', function ($q) use ($filters, $activeYear) {
            $q->where('classroom_students.classroom_id', $filters['classroom_id'])
                ->where('classroom_students.academic_year_id', $activeYear->id)
                ->where('classroom_students.status', 'aktif');
        })
            ->when($schedule && $schedule->religion_id, fn ($q) => $q->where('religion_id', $schedule->religion_id))
            ->orderBy('full_name', 'asc')
            ->get();

        $subject = Subject::find($filters['subject_id']);
        if ($subject && $schedule && $schedule->religion) {
            $subject = clone $subject;
            if (!str_contains($subject->name, '(' . $schedule->religion->name . ')')) {
                $subject->name .= ' (' . $schedule->religion->name . ')';
            }
        }

        return [
            'components' => $components,
            'students' => $students,
            'classroom' => Classroom::find($filters['classroom_id']),
            'subject' => $subject,
        ];
    }

    /**
     * Menyiapkan data untuk form Edit
     */
    public function getEditData(int $id)
    {
        $item = GradingItem::with(['studentGrades', 'component'])->findOrFail($id);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Ambil data pendukung (sama seperti create tapi berdasarkan item yang ada)
        if (! $item->classroom_id || ! $item->component) {
            // Bapak bisa tambahkan log atau dd di sini untuk debug
            dd('Data tidak lengkap', $item->toArray());
        }
        $filters = [
            'classroom_id' => $item->classroom_id,
            'subject_id' => $item->component->subject_id,
        ];

        $extraData = $this->getCreateData($filters);

        return array_merge(['item' => $item], $extraData);
    }
}
