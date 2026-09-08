<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtBank;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Akademik\Models\Classroom;
use Modules\Cbt\Services\CbtGradingService;
use Modules\Cbt\Exports\CbtExamResultsExport;
use App\Services\ActivityLogger;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Cbt\Models\CbtCttExamSummary;
use Modules\Cbt\Models\CbtCttItemAnalysis;
use Modules\Cbt\Models\CbtCttStudentResult;
use Modules\Cbt\Models\CbtIrtItemParameter;
use Modules\Cbt\Models\CbtIrtStudentAbility;
use Modules\Cbt\Models\CbtAnalysisJob;
use Modules\Cbt\Jobs\ProcessExamAnalyticsJob;
use Modules\Cbt\Services\CttAnalyticsService;
use Modules\Penilaian\Models\GradingItem;
use Modules\Penilaian\Models\StudentGrade;
use Modules\Akademik\Models\ClassroomStudent;

class CbtExamController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isCbtManager = $user->hasRole('admin') || $user->can('manage-cbt');

        // Base query untuk count & filtering
        $baseQuery = CbtExam::query();
        if (!$isCbtManager && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            $baseQuery->where('teacher_id', $teacherId);
        }

        $allCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('is_active', true)->count();
        $inactiveCount = (clone $baseQuery)->where('is_active', false)->count();

        $query = CbtExam::with(['bank.subject', 'teacher', 'classrooms']);

        // Jika bukan admin / pengelola CBT, hanya bisa melihat sesi miliknya sendiri
        if (!$isCbtManager && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            $query->where('teacher_id', $teacherId);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->search) {
            $search = $request->search;
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        $exams = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Ambil bank soal untuk dropdown form (eager-load mapel dan guru)
        $bankQuery = CbtBank::with(['subject', 'teacher']);
        if (!$isCbtManager && $user->hasRole('guru')) {
            $bankQuery->where('teacher_id', $user->teacher?->id);
        }
        $banks = $bankQuery->orderBy('name')->get();

        $subjects = collect();
        $gradingComponents = collect();
        
        $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();

        // Ambil semua rombel aktif di tahun ajaran berjalan (misal 18 rombel: X 1 - XII 6)
        $allActiveClasses = Classroom::when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        if (!$isCbtManager && $user->hasRole('guru')) {
            $schedules = \Modules\Akademik\Models\Schedule::with(['subject', 'religion', 'classroom'])
                ->where('teacher_id', $user->teacher?->id)
                ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
                ->get();
                
            $schedules->each(function ($schedule) {
                if ($schedule->subject && $schedule->subject->is_religion && $schedule->religion) {
                    $clone = clone $schedule->subject;
                    if (!str_contains($clone->name, '(' . $schedule->religion->name . ')')) {
                        $clone->name .= ' (' . $schedule->religion->name . ')';
                    }
                    $schedule->setRelation('subject', $clone);
                }
            });

            // Build a map of classroom ID => array of subject IDs from the teacher's schedules
            $classroomSubjectMap = [];
            foreach ($schedules as $schedule) {
                if ($schedule->classroom && $schedule->subject) {
                    $cid = $schedule->classroom->id;
                    $sid = $schedule->subject->id;
                    $classroomSubjectMap[$cid][] = $sid;
                }
            }

            $classrooms = $schedules->pluck('classroom')
                ->filter()
                ->unique('id')
                ->values()
                ->map(function($classroom) use ($classroomSubjectMap) {
                    $classroom->subject_ids = $classroomSubjectMap[$classroom->id] ?? [];
                    return [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'subject_ids' => $classroom->subject_ids,
                    ];
                });

            $subjects = $schedules->pluck('subject')
                ->filter()
                ->unique('id')
                ->values();

            $gradingComponentsQuery = \Modules\Penilaian\Models\GradingComponent::with('subject')
                ->where('academic_year_id', $activeYear?->id)
                ->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->teacher?->id)
                      ->orWhereNull('teacher_id');
                });
            $gradingComponents = $gradingComponentsQuery->get();
        } else {
            $classrooms = $allActiveClasses;
            $subjects = \Modules\Akademik\Models\Subject::orderBy('name')->get();
            $gradingComponents = \Modules\Penilaian\Models\GradingComponent::with('subject')
                ->where('academic_year_id', $activeYear?->id)->get();
        }
        
        $teachers = \Modules\Akademik\Models\Teacher::orderBy('full_name')->get();
        $sessions = \Modules\Cbt\Models\CbtSession::whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time']);

        return Inertia::render('Cbt/Exam/Index', [
            'exams' => $exams,
            'banks' => $banks,
            'classrooms' => $classrooms,
            'allClassrooms' => $allActiveClasses,
            'isCbtManager' => $isCbtManager,
            'subjects' => $subjects,
            'gradingComponents' => $gradingComponents,
            'teachers' => $teachers,
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'status']),
            'counts' => [
                'all' => $allCount,
                'active' => $activeCount,
                'inactive' => $inactiveCount,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cbt_bank_id' => 'required|exists:cbt_banks,id',
            'title' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'shuffle_questions' => 'required|boolean',
            'shuffle_options' => 'required|boolean',
            'must_complete_all' => 'required|boolean',
            'is_active' => 'required|boolean',
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'exists:classrooms,id',
            'grading_component_id' => 'nullable|exists:grading_components,id',
            'is_independent' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $teacherId = $user->teacher?->id;

        if (!$teacherId && !$user->hasRole('admin')) {
            return redirect()->back()->with('error', 'Akun Anda tidak terhubung dengan data Guru.');
        }

        if ($user->hasRole('admin') && !$teacherId) {
            $teacherId = null;
        }

        $exam = CbtExam::create([
            'cbt_bank_id' => $request->cbt_bank_id,
            'teacher_id' => $teacherId,
            'title' => $request->title,
            'duration' => $request->duration,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'shuffle_questions' => $request->shuffle_questions,
            'shuffle_options' => $request->shuffle_options,
            'must_complete_all' => $request->must_complete_all,
            'is_independent' => $request->is_independent ?? false,
            'is_active' => $request->is_active,
            'grading_component_id' => $request->grading_component_id,
        ]);

        $exam->classrooms()->sync($request->classroom_ids);

        // Auto-generate proctoring untuk Ujian Mandiri
        if ($request->is_independent && $teacherId) {
            // Buat atau ambil Sesi Ujian Mandiri
            $session = \Modules\Cbt\Models\CbtSession::firstOrCreate(
                ['name' => 'Sesi Ujian Mandiri'],
                ['start_time' => '07:00:00', 'end_time' => '15:00:00']
            );

            foreach ($request->classroom_ids as $classId) {
                $classroom = \Modules\Akademik\Models\Classroom::with('students')->find($classId);
                if (!$classroom) continue;

                // Buat atau ambil Ruang sesuai nama Kelas
                $roomName = 'Ruang ' . $classroom->name;
                $room = \Modules\Cbt\Models\CbtRoom::firstOrCreate(
                    ['name' => $roomName],
                    ['capacity' => 36]
                );

                // Assign siswa kelas ini ke ruang tersebut (seat 1-36)
                $students = $classroom->students()->where('classroom_students.status', 'aktif')->get();
                $seat = 1;
                foreach ($students as $student) {
                    if ($seat > 36) break; // Maksimal UI proktor saat ini 36
                    \Modules\Cbt\Models\CbtRoomStudent::updateOrCreate(
                        [
                            'cbt_room_id' => $room->id,
                            'student_id' => $student->id
                        ],
                        [
                            'seat_number' => $seat
                        ]
                    );
                    $seat++;
                }

                // Buat Jadwal Proktor
                \Modules\Cbt\Models\CbtProctorSchedule::firstOrCreate(
                    [
                        'date' => \Carbon\Carbon::parse($request->start_time)->toDateString(),
                        'cbt_room_id' => $room->id,
                        'cbt_session_id' => $session->id,
                    ],
                    [
                        'teacher_id' => $teacherId,
                        'status' => 'not_started',
                    ]
                );
            }
        }

        if ($exam->grading_component_id) {
            self::syncExamGradesToPenilaian($exam);
        }

        ActivityLogger::log(
            'CBT_EXAM_CREATE',
            "Membuat Jadwal Ujian CBT baru: {$exam->title} (Durasi: {$exam->duration} menit, Mulai: {$exam->start_time})",
            $exam,
            null,
            $exam->toArray()
        );

        return redirect()->back()->with('success', 'Jadwal Ujian berhasil dibuat.');
    }

    /**
     * Menyimpan banyak jadwal ujian terpusat (PTS/PAS/SAS) sekaligus dalam 1 transaksi.
     */
    public function batchStore(Request $request)
    {
        $request->validate([
            'exams' => 'required|array|min:1',
            'exams.*.cbt_bank_id' => 'required|exists:cbt_banks,id',
            'exams.*.title' => 'required|string|max:255',
            'exams.*.duration' => 'required|integer|min:1',
            'exams.*.start_time' => 'required|date',
            'exams.*.end_time' => 'required|date|after:exams.*.start_time',
            'exams.*.classroom_ids' => 'required|array|min:1',
            'exams.*.classroom_ids.*' => 'exists:classrooms,id',
            'exams.*.shuffle_questions' => 'required|boolean',
            'exams.*.shuffle_options' => 'required|boolean',
            'exams.*.must_complete_all' => 'required|boolean',
            'exams.*.is_active' => 'required|boolean',
            'exams.*.is_independent' => 'nullable|boolean',
            'exams.*.grading_component_id' => 'nullable|exists:grading_components,id',
        ]);

        $user = Auth::user();
        $teacherId = $user->teacher?->id;

        if (!$teacherId && !$user->hasRole('admin') && !$user->can('manage-cbt')) {
            return redirect()->back()->with('error', 'Akun Anda tidak memiliki hak akses penjadwalan CBT.');
        }

        if ($user->hasRole('admin') && !$teacherId) {
            $teacherId = null;
        }

        $createdCount = 0;
        DB::transaction(function () use ($request, $teacherId, &$createdCount) {
            foreach ($request->exams as $item) {
                $bank = CbtBank::find($item['cbt_bank_id']);
                $itemTeacherId = $bank?->teacher_id ?? $teacherId;

                $exam = CbtExam::create([
                    'cbt_bank_id' => $item['cbt_bank_id'],
                    'teacher_id' => $itemTeacherId,
                    'title' => $item['title'],
                    'duration' => $item['duration'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'shuffle_questions' => $item['shuffle_questions'],
                    'shuffle_options' => $item['shuffle_options'],
                    'must_complete_all' => $item['must_complete_all'],
                    'is_independent' => $item['is_independent'] ?? false,
                    'is_active' => $item['is_active'],
                    'grading_component_id' => $item['grading_component_id'] ?? null,
                ]);

                $exam->classrooms()->sync($item['classroom_ids']);
                $createdCount++;

                ActivityLogger::log(
                    'CBT_EXAM_CREATE',
                    "Membuat Jadwal Ujian Terpusat (Batch): {$exam->title} (Durasi: {$exam->duration} menit, Mulai: {$exam->start_time})",
                    $exam,
                    null,
                    $exam->toArray()
                );
            }
        });

        return redirect()->route('cbt.exams.index')->with('success', "Berhasil menjadwalkan {$createdCount} ujian terpusat sekaligus.");
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cbt_bank_id' => 'required|exists:cbt_banks,id',
            'title' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'shuffle_questions' => 'required|boolean',
            'shuffle_options' => 'required|boolean',
            'must_complete_all' => 'required|boolean',
            'is_active' => 'required|boolean',
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'exists:classrooms,id',
            'grading_component_id' => 'nullable|exists:grading_components,id',
            'is_independent' => 'nullable|boolean',
        ]);

        $exam = CbtExam::with('classrooms')->findOrFail($id);
        $old = $exam->toArray();
        
        if ($exam->is_independent) {
            $this->cleanupIndependentProctorSchedules($exam);
        }

        $exam->update([
            'cbt_bank_id' => $request->cbt_bank_id,
            'title' => $request->title,
            'duration' => $request->duration,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'shuffle_questions' => $request->shuffle_questions,
            'shuffle_options' => $request->shuffle_options,
            'must_complete_all' => $request->must_complete_all,
            'is_independent' => $request->is_independent ?? false,
            'is_active' => $request->is_active,
            'grading_component_id' => $request->grading_component_id,
        ]);

        $exam->classrooms()->sync($request->classroom_ids);

        // Jika menjadi independent, kita generate ulang proctoringnya (opsional)
        // Untuk saat ini biarkan saja atau generate jika belum ada
        if ($request->is_independent && $exam->teacher_id) {
            $session = \Modules\Cbt\Models\CbtSession::firstOrCreate(
                ['name' => 'Sesi Ujian Mandiri'],
                ['start_time' => '07:00:00', 'end_time' => '15:00:00']
            );

            foreach ($request->classroom_ids as $classId) {
                $classroom = \Modules\Akademik\Models\Classroom::with('students')->find($classId);
                if (!$classroom) continue;

                $roomName = 'Ruang ' . $classroom->name;
                $room = \Modules\Cbt\Models\CbtRoom::firstOrCreate(
                    ['name' => $roomName],
                    ['capacity' => 36]
                );

                $students = $classroom->students()->where('classroom_students.status', 'aktif')->get();
                $seat = 1;
                foreach ($students as $student) {
                    if ($seat > 36) break;
                    \Modules\Cbt\Models\CbtRoomStudent::updateOrCreate(
                        [
                            'cbt_room_id' => $room->id,
                            'student_id' => $student->id
                        ],
                        [
                            'seat_number' => $seat
                        ]
                    );
                    $seat++;
                }

                \Modules\Cbt\Models\CbtProctorSchedule::firstOrCreate(
                    [
                        'date' => \Carbon\Carbon::parse($request->start_time)->toDateString(),
                        'cbt_room_id' => $room->id,
                        'cbt_session_id' => $session->id,
                    ],
                    [
                        'teacher_id' => $exam->teacher_id,
                        'status' => 'not_started',
                    ]
                );
            }
        }

        if ($exam->grading_component_id) {
            self::syncExamGradesToPenilaian($exam);
        }

        ActivityLogger::log(
            'CBT_EXAM_UPDATE',
            "Memperbarui Jadwal Ujian CBT: {$exam->title} (Durasi: {$exam->duration} menit)",
            $exam,
            $old,
            $exam->toArray()
        );

        return redirect()->back()->with('success', 'Jadwal Ujian berhasil diperbarui.');
    }

    public function dryRun($id)
    {
        $user = Auth::user();
        $exam = CbtExam::with(['bank.subject', 'bank.questions'])->findOrFail($id);

        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            if ($exam->teacher_id && $exam->teacher_id != $teacherId) {
                abort(404);
            }
        }

        $bank = $exam->bank;
        if (!$bank || $bank->questions->isEmpty()) {
            return redirect()->route('cbt.exams.index')->with('error', 'Bank Soal untuk ujian ini belum memiliki butir soal.');
        }

        $sessionKey = 'cbt_dry_run_order_' . $id;

        // Pertahankan urutan soal & opsi jika ini adalah partial reload (Muat Ulang Soal)
        if (request()->header('X-Inertia-Partial-Data') && session()->has($sessionKey)) {
            $cached = session()->get($sessionKey);
            $questionIds = $cached['question_ids'] ?? [];
            $optionsOrder = $cached['options_order'] ?? [];

            // Sinkronkan jika ada soal yang dihapus dari bank soal saat simulasi
            $existingIds = $bank->questions->pluck('id')->toArray();
            $questionIds = array_values(array_intersect($questionIds, $existingIds));
        } else {
            $questionIds = $bank->questions->pluck('id')->toArray();

            if ($exam->shuffle_questions) {
                shuffle($questionIds);
            }

            $optionsOrder = [];
            if ($exam->shuffle_options) {
                foreach ($bank->questions as $q) {
                    if (in_array($q->question_type, ['pilihan_ganda', 'list', 'checklist', 'skor_berbeda'])) {
                        $keys = array_keys($q->options ?? []);
                        shuffle($keys);
                        $optionsOrder[$q->id] = $keys;
                    }
                }
            }

            // Simpan urutan acak simulasi ke session
            session()->put($sessionKey, [
                'question_ids' => $questionIds,
                'options_order' => $optionsOrder,
            ]);
        }

        $dbQuestions = $bank->questions->keyBy('id');

        $questions = collect($questionIds)->map(function ($qId) use ($dbQuestions, $optionsOrder, $exam) {
            $q = $dbQuestions->get($qId);
            if (!$q) return null;

            $options = $q->options;
            $savedOptionOrder = $optionsOrder[$q->id] ?? null;

            if ($exam->shuffle_options && $savedOptionOrder && in_array($q->question_type, ['pilihan_ganda', 'list', 'checklist', 'skor_berbeda'])) {
                $shuffledOptions = [];
                foreach ($savedOptionOrder as $key) {
                    if (isset($options[$key])) {
                        $shuffledOptions[$key] = $options[$key];
                    }
                }
                $options = $shuffledOptions;
            }

            return [
                'id' => $q->id,
                'cbt_bank_id' => $q->cbt_bank_id,
                'question_type' => $q->question_type,
                'question_text' => $q->question_text,
                'options' => $options,
                'score' => (float)($q->score ?: 10),
                'lock_n' => (bool)$q->lock_n,
                'grouping' => $q->grouping,
                'correct_answer' => $q->correct_answer,
            ];
        })->filter()->values();

        $studentExam = [
            'id' => 0,
            'cbt_exam_id' => $exam->id,
            'student_id' => 0,
            'status' => 'started',
            'started_at' => now()->toIso8601String(),
            'warning_count' => 0,
            'is_blocked' => false,
            'student' => [
                'id' => 0,
                'full_name' => $user->name . ' (Simulasi Guru)',
                'nisn' => 'SIMULASI-GURU',
            ],
        ];

        $durationSeconds = ($exam->duration ?: 60) * 60;

        return Inertia::render('Cbt/Student/ExamSession', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'duration' => $exam->duration ?: 60,
                'shuffle_questions' => (bool)$exam->shuffle_questions,
                'shuffle_options' => (bool)$exam->shuffle_options,
                'must_complete_all' => (bool)$exam->must_complete_all,
                'bank' => [
                    'id' => $bank->id,
                    'name' => $bank->name,
                    'subject' => [
                        'name' => $bank->subject?->name ?? 'Mata Pelajaran',
                    ],
                ],
            ],
            'studentExam' => $studentExam,
            'questions' => $questions,
            'initialAnswers' => (object)[],
            'remainingSeconds' => $durationSeconds,
            'suspensionRemainingSeconds' => 0,
            'isDryRun' => true,
        ]);
    }

    public function destroy($id)
    {
        $exam = CbtExam::with('classrooms')->findOrFail($id);
        $examTitle = $exam->title;
        $old = $exam->toArray();

        if ($exam->is_independent) {
            $this->cleanupIndependentProctorSchedules($exam);
        }

        $exam->classrooms()->detach();
        $exam->delete();

        ActivityLogger::log(
            'CBT_EXAM_DELETE',
            "Menghapus Jadwal Ujian CBT: {$examTitle}",
            $exam,
            $old,
            null
        );

        return redirect()->back()->with('success', 'Jadwal Ujian berhasil dihapus.');
    }

    public function toggleIndependent(Request $request, $id)
    {
        $exam = CbtExam::with('classrooms')->findOrFail($id);
        $newValue = !$exam->is_independent;
        
        if (!$newValue) {
            $this->cleanupIndependentProctorSchedules($exam);
        }
        
        $exam->update(['is_independent' => $newValue]);

        // Jika diaktifkan, buat proktor setup otomatis (sama seperti di store/update)
        if ($newValue && $exam->teacher_id) {
            $session = \Modules\Cbt\Models\CbtSession::firstOrCreate(
                ['name' => 'Sesi Ujian Mandiri'],
                ['start_time' => '07:00:00', 'end_time' => '15:00:00']
            );

            foreach ($exam->classrooms as $classroom) {
                $classroom->load('students');
                $roomName = 'Ruang ' . $classroom->name;
                $room = \Modules\Cbt\Models\CbtRoom::firstOrCreate(
                    ['name' => $roomName],
                    ['capacity' => 36]
                );

                $students = $classroom->students()
                    ->where('classroom_students.status', 'aktif')
                    ->get();

                $seat = 1;
                foreach ($students as $student) {
                    if ($seat > 36) break;
                    \Modules\Cbt\Models\CbtRoomStudent::updateOrCreate(
                        ['cbt_room_id' => $room->id, 'student_id' => $student->id],
                        ['seat_number' => $seat]
                    );
                    $seat++;
                }

                \Modules\Cbt\Models\CbtProctorSchedule::firstOrCreate(
                    [
                        'date' => \Carbon\Carbon::parse($exam->start_time)->toDateString(),
                        'cbt_room_id' => $room->id,
                        'cbt_session_id' => $session->id,
                    ],
                    [
                        'teacher_id' => $exam->teacher_id,
                        'status' => 'not_started',
                    ]
                );
            }
        }

        ActivityLogger::log(
            'CBT_EXAM_TOGGLE_INDEPENDENT',
            "Mengubah mode Ujian CBT: {$exam->title} menjadi " . ($newValue ? 'Ujian Mandiri' : 'Ujian Reguler Proktor'),
            $exam
        );

        $label = $newValue ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Ujian Mandiri berhasil {$label}.");
    }

    public function toggleActive(Request $request, $id)
    {
        $exam = CbtExam::findOrFail($id);
        $newValue = !$exam->is_active;
        $exam->update(['is_active' => $newValue]);

        ActivityLogger::log(
            'CBT_EXAM_TOGGLE_ACTIVE',
            "Mengubah status Ujian CBT: {$exam->title} menjadi " . ($newValue ? 'AKTIF' : 'NON-AKTIF'),
            $exam
        );

        $label = $newValue ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Status Ujian \"{$exam->title}\" berhasil {$label}.");
    }

    private function cleanupIndependentProctorSchedules(CbtExam $exam)
    {
        if (!$exam->teacher_id) return;
        
        $session = \Modules\Cbt\Models\CbtSession::where('name', 'Sesi Ujian Mandiri')->first();
        if (!$session) return;
        
        $examDate = \Carbon\Carbon::parse($exam->start_time)->toDateString();
        
        foreach ($exam->classrooms as $classroom) {
            $roomName = 'Ruang ' . $classroom->name;
            $room = \Modules\Cbt\Models\CbtRoom::where('name', $roomName)->first();
            
            if ($room) {
                $otherExams = \Modules\Cbt\Models\CbtExam::where('is_independent', true)
                    ->where('teacher_id', $exam->teacher_id)
                    ->whereDate('start_time', $examDate)
                    ->where('id', '!=', $exam->id)
                    ->whereHas('classrooms', function($q) use ($classroom) {
                        $q->where('classrooms.id', $classroom->id);
                    })
                    ->exists();

                if (!$otherExams) {
                    \Modules\Cbt\Models\CbtProctorSchedule::where('date', $examDate)
                        ->where('cbt_room_id', $room->id)
                        ->where('cbt_session_id', $session->id)
                        ->where('teacher_id', $exam->teacher_id)
                        ->delete();
                }
            }
        }
    }

    public static function syncExamGradesToPenilaian(CbtExam $exam)
    {
        if (!$exam->grading_component_id) {
            return;
        }

        try {
            $exam->loadMissing(['classrooms', 'studentExams']);
            $submittedExams = $exam->studentExams->where('status', 'submitted')->whereNotNull('score');

            foreach ($exam->classrooms as $classroom) {
                $gradingItem = GradingItem::firstOrCreate(
                    [
                        'grading_component_id' => $exam->grading_component_id,
                        'classroom_id' => $classroom->id,
                        'title' => $exam->title,
                    ],
                    [
                        'date' => \Carbon\Carbon::parse($exam->start_time)->toDateString(),
                    ]
                );

                $gradingItem->update([
                    'grading_component_id' => $exam->grading_component_id,
                    'title' => $exam->title,
                ]);

                // ID siswa aktif di kelas ini
                $studentIdsInClass = ClassroomStudent::where('classroom_id', $classroom->id)
                    ->where('status', 'aktif')
                    ->pluck('student_id')
                    ->toArray();

                foreach ($submittedExams as $studentExam) {
                    if (in_array($studentExam->student_id, $studentIdsInClass)) {
                        StudentGrade::updateOrCreate(
                            [
                                'grading_item_id' => $gradingItem->id,
                                'student_id' => $studentExam->student_id,
                            ],
                            [
                                'score' => $studentExam->score,
                                'note' => 'CBT: ' . $exam->title,
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal menyinkronkan nilai CBT ke modul Penilaian: ' . $e->getMessage());
        }
    }

    private function checkExamOwnership(CbtExam $exam)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            if (!$teacherId || $exam->teacher_id !== $teacherId) {
                abort(404);
            }
        }
    }

    public function results($id)
    {
        $exam = CbtExam::with(['bank.subject', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);

        $classroomIds = $exam->classrooms->pluck('id');

        // Ambil semua siswa yang berada di kelas yang ditargetkan
        $students = \Modules\Akademik\Models\Student::whereHas('classrooms', function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            })
            ->with(['classrooms' => function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            }])
            ->orderBy('full_name')
            ->get();

        // Ambil total soal di bank soal ini sebagai fallback
        $bankQuestionsCount = CbtQuestion::where('cbt_bank_id', $exam->cbt_bank_id)->count();

        // Eager load status ujian siswa dan jawaban untuk cbt_exam ini
        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->with('answers')
            ->get()
            ->keyBy('student_id');

        // Eager load CTT & IRT analytics data
        $cttSummary = CbtCttExamSummary::where('cbt_exam_id', $id)->first();
        $cttItemAnalyses = CbtCttItemAnalysis::where('cbt_exam_id', $id)
            ->with('question:id,question_text,question_type,score')
            ->get()
            ->values()
            ->map(function ($item, $idx) {
                $item->item_number = $idx + 1;
                return $item;
            });

        $irtItemParameters = CbtIrtItemParameter::where('cbt_exam_id', $id)
            ->with('question:id,question_text,question_type,score')
            ->get()
            ->values()
            ->map(function ($item, $idx) {
                $item->item_number = $idx + 1;
                return $item;
            });
        $irtJob = CbtAnalysisJob::where('cbt_exam_id', $id)->where('job_type', 'LIKE', 'IRT_%')->latest()->first();

        $studentExamIds = $studentExams->pluck('id')->filter()->toArray();
        $cttResults = !empty($studentExamIds) ? CbtCttStudentResult::whereIn('cbt_student_exam_id', $studentExamIds)->get()->keyBy('cbt_student_exam_id') : collect();
        $irtAbilities = !empty($studentExamIds) ? CbtIrtStudentAbility::whereIn('cbt_student_exam_id', $studentExamIds)->get()->keyBy('cbt_student_exam_id') : collect();

        // Format data untuk dikirim ke Inertia
        $results = $students->map(function ($student) use ($studentExams, $bankQuestionsCount, $cttResults, $irtAbilities) {
            $examSession = $studentExams->get($student->id);
            $answeredCount = 0;
            $totalQuestions = $bankQuestionsCount;

            $cttRes = $examSession ? $cttResults->get($examSession->id) : null;
            $irtAb = $examSession ? $irtAbilities->get($examSession->id) : null;

            if ($examSession) {
                $qOrder = $examSession->question_order;
                if (is_array($qOrder) && count($qOrder) > 0) {
                    $totalQuestions = count($qOrder);
                }

                if ($examSession->relationLoaded('answers')) {
                    $answeredCount = $examSession->answers->filter(function ($ans) {
                        $val = $ans->selected_answer;
                        if ($val === null || $val === '') return false;
                        if (is_array($val)) return count($val) > 0;
                        return true;
                    })->unique('cbt_question_id')->count();
                }
            }

            return [
                'student_id'      => $student->id,
                'name'            => $student->full_name,
                'nisn'            => $student->nisn,
                'classroom_name'  => $student->classrooms->first()->name ?? '-',
                'student_exam_id' => $examSession ? $examSession->id : null,
                'status'          => $examSession ? $examSession->status : 'not_started',
                'submit_type'     => $examSession ? $examSession->submit_type : null,
                'warning_count'   => $examSession ? (int)$examSession->warning_count : 0,
                'is_blocked'      => $examSession ? (bool)$examSession->is_blocked : false,
                'answered_count'  => $answeredCount,
                'total_questions' => $totalQuestions,
                'started_at'      => $examSession?->started_at?->toIso8601String(),
                'submitted_at'    => $examSession?->submitted_at?->toIso8601String(),
                'score'           => $examSession ? $examSession->score : null,
                'ctt_result'      => $cttRes ? [
                    'raw_score'     => $cttRes->raw_score,
                    'percentage'    => $cttRes->percentage,
                    'rank'          => $cttRes->rank,
                    'correct_count' => $cttRes->correct_count,
                    'wrong_count'   => $cttRes->wrong_count,
                ] : null,
                'irt_ability'     => $irtAb ? [
                    'theta'          => $irtAb->theta,
                    'standard_error' => $irtAb->standard_error,
                    'scaled_score'   => $irtAb->scaled_score,
                    'percentile'     => $irtAb->percentile,
                ] : null,
            ];
        });

        return Inertia::render('Cbt/Exam/Results', [
            'exam'              => $exam,
            'results'           => $results,
            'ctt_summary'       => $cttSummary,
            'ctt_item_analyses' => $cttItemAnalyses,
            'irt_parameters'    => $irtItemParameters,
            'irt_job_status'    => $irtJob ? [
                'job_type'           => $irtJob->job_type,
                'status'             => $irtJob->status,
                'total_participants' => $irtJob->total_participants,
                'progress_percent'   => $irtJob->progress_percent,
                'error_message'      => $irtJob->error_message,
                'started_at'         => $irtJob->started_at?->toIso8601String(),
                'completed_at'       => $irtJob->completed_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Trigger recalculation of CTT and IRT Analytics with prior Regrading.
     */
    public function recalculateAnalytics($id, CttAnalyticsService $cttService)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        // 1. Regrade seluruh peserta ujian yang sudah submit / selesai berdasarkan kunci jawaban & bobot terbaru
        $studentExams = CbtStudentExam::where('cbt_exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'completed'])
            ->get();

        foreach ($studentExams as $stExam) {
            CbtGradingService::gradeExam($stExam->id, $stExam->submit_type);
        }

        // 2. Hitung CTT secara langsung agar hasil analisis langsung muncul saat page reload
        $cttService->calculateExamCtt($exam->id);

        // 3. Dispatch background job untuk estimasi IRT ke Microservice
        ProcessExamAnalyticsJob::dispatch($exam->id);

        ActivityLogger::log('CBT_RECALCULATE_ANALYTICS', "Guru memicu ulang regrade nilai dan kalkulasi analisis CTT & IRT untuk ujian {$exam->title}", $exam);

        return redirect()->route('cbt.exams.results', $id)->with('success', 'Nilai seluruh siswa berhasil dinilai ulang (regrade) & analisis CTT/IRT berhasil diperbarui!');
    }

    public function resetStudentExam($id, $studentExamId)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $studentExam = CbtStudentExam::where('cbt_exam_id', $id)->findOrFail($studentExamId);
        $studentName = $studentExam->student->full_name ?? 'Siswa';
        
        ActivityLogger::log('CBT_RESET_TOTAL', "Guru mereset total pengerjaan ujian siswa {$studentName} (jawaban dihapus)", $studentExam);

        // Hapus detail jawaban siswa dan hapus sesi ujiannya
        $studentExam->answers()->delete();
        $studentExam->delete();

        return redirect()->route('cbt.exams.results', $id)->with('success', 'Sesi ujian siswa berhasil di-reset. Siswa dapat memulai ulang ujian.');
    }

    public function forceSubmitStudentExam($id, $studentExamId)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $studentExam = CbtStudentExam::where('cbt_exam_id', $id)->findOrFail($studentExamId);
        $studentName = $studentExam->student->full_name ?? 'Siswa';

        // Panggil GradingService untuk menilai dan menutup ujian siswa secara paksa
        CbtGradingService::gradeExam($studentExamId, 'system_teacher');

        ActivityLogger::log('CBT_FORCE_SUBMIT', "Guru menyelesai-paksa ujian siswa {$studentName}", $studentExam);

        return redirect()->route('cbt.exams.results', $id)->with('success', 'Ujian siswa berhasil diselesaikan secara paksa dan nilai telah dikalkulasi.');
    }

    public function forceSubmitAllStudentExams($id, CttAnalyticsService $cttService)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $activeStudentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->whereIn('status', ['login', 'started'])
            ->get();

        $count = $activeStudentExams->count();

        if ($count === 0) {
            return redirect()->route('cbt.exams.results', $id)->with('error', 'Tidak ada siswa yang sedang dalam status aktif atau mengerjakan ujian.');
        }

        foreach ($activeStudentExams as $stExam) {
            CbtGradingService::gradeExam($stExam->id, 'system_teacher');
        }

        // Hitung ulang analisis CTT secara langsung
        $cttService->calculateExamCtt($exam->id);

        $actorName = Auth::user()->name ?? 'Guru/Admin';
        ActivityLogger::log('CBT_FORCE_SUBMIT_ALL', "{$actorName} menyelesai-paksa seluruh ujian siswa aktif ({$count} siswa) untuk ujian {$exam->title}", $exam);

        return redirect()->route('cbt.exams.results', $id)->with('success', "Berhasil menyelesaikan dan menilai {$count} pengerjaan siswa secara serentak.");
    }

    /**
     * Izinkan siswa yang di-kick (logged_out) untuk masuk kembali ke ujian.
     * Khusus untuk ujian mandiri (is_independent = true) yang tidak punya proktor/token.
     * Guru memberikan izin re-entry dengan mengubah status siswa kembali ke 'login'.
     */
    public function allowReenterStudent($id, $studentExamId)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $studentExam = CbtStudentExam::where('cbt_exam_id', $id)->findOrFail($studentExamId);

        // Pastikan hanya siswa yang berstatus logged_out yang bisa di-allow (bukan yang sudah ban)
        if ($studentExam->is_blocked || $studentExam->status === 'submitted') {
            return redirect()->route('cbt.exams.results', $id)
                ->with('error', 'Siswa ini sudah diblokir permanen atau telah menyelesaikan ujian. Tidak dapat memberi izin masuk kembali.');
        }

        if ($studentExam->status !== 'logged_out') {
            return redirect()->route('cbt.exams.results', $id)
                ->with('error', 'Siswa ini tidak dalam status dikeluarkan (logged_out).');
        }

        // Reset status ke 'login' sehingga siswa bisa masuk kembali
        // Jawaban & urutan soal yang sudah ada TIDAK dihapus — siswa melanjutkan dari soal terakhir
        $studentExam->update([
            'status'        => 'login',
            'blocked_until' => null,
        ]);

        // Otomatis buka kembali jadwal ruangan jika sebelumnya statusnya 'ended'
        $roomStudents = \Modules\Cbt\Models\CbtRoomStudent::where('student_id', $studentExam->student_id)->get();
        foreach ($roomStudents as $rs) {
            \Modules\Cbt\Models\CbtProctorSchedule::where('cbt_room_id', $rs->cbt_room_id)
                ->where('date', now()->toDateString())
                ->where('status', 'ended')
                ->update(['status' => 'started']);
        }

        $actorName = Auth::user()->name ?? 'Guru/Admin';
        ActivityLogger::log('CBT_ALLOW_REENTER', "{$actorName} memberikan izin masuk kembali (re-entry) untuk siswa {$studentExam->student->full_name}", $studentExam);

        return redirect()->route('cbt.exams.results', $id)
            ->with('success', "Siswa {$studentExam->student->full_name} telah diberi izin untuk masuk kembali ke ujian.");
    }

    /**
     * Reset status selesai (submitted/blocked/logged_out) kembali ke 'started'.
     * Membatalkan status dikumpulkan, menghapus nilai dan waktu submit,
     * tetapi TETAP MENJAGA jawaban yang pernah diisi siswa agar tidak hilang.
     */
    public function reopenStudentExam($id, $studentExamId)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $studentExam = CbtStudentExam::where('cbt_exam_id', $id)->findOrFail($studentExamId);

        if ($studentExam->is_blocked || $studentExam->warning_count >= 4) {
            return redirect()->route('cbt.exams.results', $id)
                ->with('error', "Siswa {$studentExam->student->full_name} telah diblokir permanen akibat pelanggaran fokus (Strike 4+). Status ujian tidak dapat di-reset kembali. Gunakan tombol 'Reset Total Sesi' jika ingin mengizinkan siswa mengulang ujian dari awal.");
        }

        $oldStatus = $studentExam->status;

        $studentExam->update([
            'status'        => 'started',
            'score'         => null,
            'submitted_at'  => null,
            'submit_type'   => null,
            'is_blocked'    => false,
            'blocked_until' => null,
        ]);

        // Otomatis buka kembali jadwal ruangan jika sebelumnya statusnya 'ended'
        $roomStudents = \Modules\Cbt\Models\CbtRoomStudent::where('student_id', $studentExam->student_id)->get();
        foreach ($roomStudents as $rs) {
            \Modules\Cbt\Models\CbtProctorSchedule::where('cbt_room_id', $rs->cbt_room_id)
                ->where('date', now()->toDateString())
                ->where('status', 'ended')
                ->update(['status' => 'started']);
        }

        $actorName = Auth::user()->name ?? 'Guru/Admin';
        ActivityLogger::log('CBT_ADMIN_REOPEN_EXAM', "{$actorName} membuka kembali ujian siswa {$studentExam->student->full_name} (Status awal: {$oldStatus})", $studentExam);

        return redirect()->route('cbt.exams.results', $id)
            ->with('success', "Status selesai untuk siswa {$studentExam->student->full_name} berhasil di-reset. Ujian dibuka kembali dan siswa dapat melanjutkan.");
    }

    public function exportResults($id)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $fileName = 'rekap_hasil_' . str_replace(' ', '_', strtolower($exam->title)) . '_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(new CbtExamResultsExport($id), $fileName);
    }

    public function recap($id)
    {
        $exam = CbtExam::with(['bank.subject', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);
        $questions = CbtQuestion::where('cbt_bank_id', $exam->cbt_bank_id)
            ->orderBy('id')
            ->get();

        $classroomIds = $exam->classrooms->pluck('id');

        $students = \Modules\Akademik\Models\Student::whereHas('classrooms', function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            })
            ->with(['classrooms' => function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            }])
            ->orderBy('full_name')
            ->get();

        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->get()
            ->keyBy('student_id');

        $headers = [];
        $columnsMap = [];
        
        foreach ($questions as $index => $q) {
            $type = $q->question_type;
            $correct = $q->correct_answer;

            $isSplit = false;
            if (is_array($correct) && !empty($correct)) {
                $keys = array_keys($correct);
                if ($keys !== range(0, count($correct) - 1)) {
                    $isSplit = true;
                }
            }

            if ($isSplit) {
                foreach ($correct as $subKey => $subVal) {
                    $keyText = is_array($subVal) ? implode(' / ', $subVal) : (string)$subVal;
                    $headers[] = [
                        'label' => "Soal " . ($index + 1) . " - " . $subKey,
                        'key' => "q_{$q->id}_{$subKey}",
                        'correct' => $keyText,
                        'type' => $type
                    ];
                    $columnsMap[] = [
                        'question_id' => $q->id,
                        'type' => $type,
                        'is_split' => true,
                        'sub_key' => $subKey,
                        'correct' => $subVal
                    ];
                }
            } else {
                $keyText = CbtExamResultsExport::formatAnswerKey($type, $correct);
                $headers[] = [
                    'label' => "Soal " . ($index + 1),
                    'key' => "q_{$q->id}",
                    'correct' => $keyText,
                    'type' => $type
                ];
                $columnsMap[] = [
                    'question_id' => $q->id,
                    'type' => $type,
                    'is_split' => false,
                    'correct' => $keyText
                ];
            }
        }

        $matrix = [];

        foreach ($students as $student) {
            $studentExam = $studentExams->get($student->id);
            $studentRow = [
                'name' => $student->full_name,
                'nisn' => $student->nisn,
                'classroom_name' => $student->classrooms->first()->name ?? '-',
                'score' => $studentExam ? $studentExam->score : null,
                'answers' => [],
            ];

            if ($studentExam) {
                $answers = CbtStudentAnswer::where('cbt_student_exam_id', $studentExam->id)
                    ->get()
                    ->keyBy('cbt_question_id');

                foreach ($columnsMap as $col) {
                    $qId = $col['question_id'];
                    $ans = $answers->get($qId);
                    $selected = $ans ? $ans->selected_answer : null;

                    if (is_string($selected)) {
                        $decoded = json_decode($selected, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $selected = $decoded;
                        }
                    }

                    if ($col['is_split']) {
                        $subKey = $col['sub_key'];
                        if ($col['type'] === 'list' || $col['type'] === 'checklist') {
                            if (!$ans || is_null($ans->selected_answer)) {
                                $ansVal = '-';
                            } else {
                                $selArr = is_array($selected) ? $selected : ($selected ? [$selected] : []);
                                $selArr = array_map('strval', $selArr);
                                $ansVal = in_array((string)$subKey, $selArr) ? 'CHECK' : '-CHECK';
                            }
                        } else {
                            $ansVal = (is_array($selected) && isset($selected[$subKey])) ? (string)$selected[$subKey] : '-';
                        }
                        
                        $isCorrect = false;
                        if ($ansVal !== '-') {
                            $cleanAnsVal = strtolower(trim($ansVal));
                            $corrAlts = $col['correct'];
                            if (is_array($corrAlts)) {
                                $corrAltsList = array_map(fn($x) => strtolower(trim((string)$x)), $corrAlts);
                                if (in_array($cleanAnsVal, $corrAltsList)) {
                                    $isCorrect = true;
                                }
                            } else {
                                $cleanCorrVal = strtolower(trim((string)$corrAlts));
                                if ($cleanAnsVal === $cleanCorrVal) {
                                    $isCorrect = true;
                                }
                            }
                        }
                        
                        $studentRow['answers'][] = [
                            'val' => $ansVal,
                            'is_correct' => $isCorrect,
                            'attempted' => $ansVal !== '-',
                        ];
                    } else {
                        if ($ans && $ans->selected_answer !== null && $ans->selected_answer !== '') {
                            $ansVal = CbtExamResultsExport::formatStudentAnswer($col['type'], $ans->selected_answer);
                            
                            $isCorrect = false;
                            $cleanVal = str_replace(' ', '', strtolower($ansVal));
                            $cleanKey = str_replace(' ', '', strtolower($col['correct']));
                            if ($cleanVal === $cleanKey) {
                                $isCorrect = true;
                            } else {
                                $alternatives = explode('/', $cleanKey);
                                if (in_array($cleanVal, $alternatives)) {
                                    $isCorrect = true;
                                }
                            }
                        } else {
                            $ansVal = '-';
                            $isCorrect = false;
                        }
                        
                        $studentRow['answers'][] = [
                            'val' => $ansVal,
                            'is_correct' => $isCorrect,
                            'attempted' => $ansVal !== '-',
                        ];
                    }
                }
            } else {
                foreach ($columnsMap as $col) {
                    $studentRow['answers'][] = [
                        'val' => '-',
                        'is_correct' => false,
                        'attempted' => false,
                    ];
                }
            }

            $matrix[] = $studentRow;
        }

        return Inertia::render('Cbt/Exam/Recap', [
            'exam' => $exam,
            'headers' => $headers,
            'matrix' => $matrix,
        ]);
    }

    /**
     * Export / Cetak PDF Laporan Data Jawaban Siswa per Sesi Ujian (Format REPORT_DATA_JAWABAN).
     */
    public function exportAnswersPdf($id)
    {
        $exam = CbtExam::with(['bank.questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'bank.subject', 'teacher', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);

        $bank = $exam->bank;
        $questions = $bank ? $bank->questions : collect();

        // Concatenate Answer Keys
        $answerKeysArr = [];
        $maxOptionCount = 5;

        foreach ($questions as $q) {
            $keyChar = '-';
            $correct = $q->correct_answer ?? $q->answer_key;
            if (!empty($correct) || $correct === 0 || $correct === '0') {
                $rawKey = is_array($correct) ? ($correct[0] ?? '-') : $correct;
                if (is_numeric($rawKey)) {
                    $keyChar = chr(65 + (int)$rawKey);
                } else {
                    $keyChar = strtoupper(trim((string)$rawKey));
                }
            } elseif (!empty($q->options)) {
                $opts = is_string($q->options) ? json_decode($q->options, true) : $q->options;
                if (is_array($opts)) {
                    foreach ($opts as $optIdx => $optVal) {
                        if (is_array($optVal) && !empty($optVal['is_correct'])) {
                            $keyChar = !empty($optVal['key']) ? strtoupper((string)$optVal['key']) : chr(65 + (int)$optIdx);
                            break;
                        }
                    }
                }
            }
            $answerKeysArr[] = substr($keyChar, 0, 1);
        }

        $answerKeysString = implode('', $answerKeysArr);

        // Fetch student exams for this specific exam session
        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->with(['student.classrooms', 'answers'])
            ->get();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];

        $classroomNamesArr = $exam->classrooms ? $exam->classrooms->pluck('name')->toArray() : [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $ansCodeArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                    $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                    $ansCodeArr[] = $optChar;

                    if ($ans->is_correct === true) {
                        $correctCount++;
                    } else {
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = 'X';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 75;

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $n > 0 ? array_sum($correctsArr) / $n : 0,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $n > 0 ? array_sum($scoresArr) / $n : 0,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
        ];

        $classroomNamesStr = !empty($classroomNamesArr) ? implode(', ', $classroomNamesArr) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_data_jawaban', [
            'bank' => $bank,
            'questions' => $questions,
            'answer_keys_string' => $answerKeysString,
            'max_option_count' => $maxOptionCount,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => $exam->start_time ? $exam->start_time->translatedFormat('l, d F Y') : now()->translatedFormat('l, d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }

    public function exportDichotomousPdf($id)
    {
        $exam = CbtExam::with(['bank.questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'bank.subject', 'teacher', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);

        $bank = $exam->bank;
        $questions = $bank ? $bank->questions : collect();

        // Fetch student exams for this specific exam session
        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->with(['student.classrooms', 'answers'])
            ->get();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];

        $classroomNamesArr = $exam->classrooms ? $exam->classrooms->pluck('name')->toArray() : [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $dichotomousArr = [];
            $ansCodeArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                    $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                    $ansCodeArr[] = $optChar;

                    if ($ans->is_correct === true) {
                        $dichotomousArr[] = '1';
                        $correctCount++;
                    } else {
                        $dichotomousArr[] = '0';
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = '-';
                    $dichotomousArr[] = '0';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 75;

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'dichotomous_arr' => $dichotomousArr,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $n > 0 ? array_sum($correctsArr) / $n : 0,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $n > 0 ? array_sum($scoresArr) / $n : 0,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
        ];

        $classroomNamesStr = !empty($classroomNamesArr) ? implode(', ', $classroomNamesArr) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_dichotomous', [
            'bank' => $bank,
            'questions' => $questions,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => $exam->start_time ? $exam->start_time->translatedFormat('l, d F Y') : now()->translatedFormat('l, d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }

    private function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $varianceSum = 0.0;
        foreach ($values as $val) {
            $varianceSum += pow($val - $mean, 2);
        }

        return sqrt($varianceSum / ($count - 1));
    }

    public function exportDaftarNilaiPdf($id)
    {
        $exam = CbtExam::with(['bank.questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'bank.subject', 'teacher', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);

        $bank = $exam->bank;
        $questions = $bank ? $bank->questions : collect();

        // Fetch student exams for this specific exam session
        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->with(['student.classrooms', 'answers'])
            ->get();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];

        $classroomNamesArr = $exam->classrooms ? $exam->classrooms->pluck('name')->toArray() : [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $ansCodeArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    if ($ans->is_correct === true) {
                        $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                        $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                        $ansCodeArr[] = $optChar;
                        $correctCount++;
                    } else {
                        $ansCodeArr[] = '-';
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = '-';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 70; // Hardcoded default passing grade in view is 70

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        
        $lulusCount = collect($studentRows)->where('is_tuntas', true)->count();
        $tidakLulusCount = collect($studentRows)->where('is_tuntas', false)->count();
        $avgScore = $n > 0 ? array_sum($scoresArr) / $n : 0;
        $diAtasRata = collect($studentRows)->filter(fn($r) => $r['score'] > $avgScore)->count();
        $diBawahRata = collect($studentRows)->filter(fn($r) => $r['score'] <= $avgScore)->count();

        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $avgScore,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $avgScore,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
            'lulus' => $lulusCount,
            'tidak_lulus' => $tidakLulusCount,
            'di_atas_rata' => $diAtasRata,
            'di_bawah_rata' => $diBawahRata,
            'peserta' => $n
        ];

        $classroomNamesStr = !empty($classroomNamesArr) ? implode(', ', $classroomNamesArr) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_daftar_nilai', [
            'bank' => $bank,
            'questions' => $questions,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => \Carbon\Carbon::parse($exam->start_time)->translatedFormat('d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }

    /**
     * Export / Cetak Berita Acara & Rekap Pelaksanaan Ujian CBT.
     */
    public function exportBeritaAcaraPdf(Request $request, $id)
    {
        $exam = CbtExam::with(['bank.subject', 'bank.questions', 'teacher.user', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);

        if ($request->has('notes') && $exam->notes !== $request->input('notes')) {
            $exam->update(['notes' => $request->input('notes')]);
        }

        $notes = $request->input('notes', $exam->notes);
        if (empty($notes)) {
            $notes = 'Ujian Computer Based Test (CBT) telah dilaksanakan secara tertib, lancar, dan sesuai dengan petunjuk teknis pelaksanaan asesmen sekolah.';
        }

        $classroomIds = $exam->classrooms->pluck('id');

        // Ambil semua siswa terdaftar di rombel kelas ujian ini
        $students = \Modules\Akademik\Models\Student::whereHas('classrooms', function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            })
            ->with(['classrooms' => function ($q) use ($classroomIds) {
                $q->whereIn('classrooms.id', $classroomIds)
                  ->where('classroom_students.status', 'aktif');
            }])
            ->orderBy('full_name')
            ->get();

        // Ambil data pengerjaan ujian siswa
        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->with(['answers'])
            ->get()
            ->keyBy('student_id');

        $presentStudents = [];
        $absentStudents = [];
        $allStudentRows = [];

        foreach ($students as $idx => $student) {
            $stExam = $studentExams->get($student->id);
            $className = $student->classrooms->first()?->name ?? '-';
            $isHadir = $stExam && in_array($stExam->status, ['started', 'submitted', 'completed']);

            $row = [
                'no' => $idx + 1,
                'id' => $student->id,
                'nis' => $student->nis ?? '-',
                'nisn' => $student->nisn ?? '-',
                'name' => $student->full_name,
                'classroom' => $className,
                'status' => $stExam ? $stExam->status : 'not_started',
                'status_label' => $this->formatStudentExamStatusLabel($stExam),
                'score' => ($stExam && !is_null($stExam->score)) ? $stExam->score : '-',
                'started_at' => $stExam?->created_at ? $stExam->created_at->format('H:i') : '-',
                'submitted_at' => $stExam?->submitted_at ? $stExam->submitted_at->format('H:i') : '-',
                'is_present' => $isHadir,
            ];

            $allStudentRows[] = $row;

            if ($isHadir) {
                $presentStudents[] = $row;
            } else {
                $absentStudents[] = $row;
            }
        }

        $totalStudents = count($students);
        $presentCount = count($presentStudents);
        $absentCount = count($absentStudents);
        $presentPercentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;
        $absentPercentage = $totalStudents > 0 ? round(($absentCount / $totalStudents) * 100, 1) : 0;

        $classroomNamesArr = $exam->classrooms ? $exam->classrooms->pluck('name')->toArray() : [];
        $classroomNamesStr = !empty($classroomNamesArr) ? implode(', ', $classroomNamesArr) : 'Semua Kelas';

        $kop = [
            'kop_pemprov' => \App\Models\Setting::get('kop_pemprov', 'PEMERINTAH PROVINSI JAWA TENGAH'),
            'kop_dinas' => \App\Models\Setting::get('kop_dinas', 'DINAS PENDIDIKAN'),
            'school_name' => \App\Models\Setting::get('school_name', config('app.name', 'SMA NEGERI 16 SEMARANG')),
            'school_address' => \App\Models\Setting::get('school_address', 'Jl. Ngadirgo Tengah, Mijen'),
            'school_city' => \App\Models\Setting::get('school_city', 'Kota Semarang'),
            'school_province' => \App\Models\Setting::get('school_province', 'Jawa Tengah'),
            'school_postal_code' => \App\Models\Setting::get('school_postal_code', ''),
            'school_phone' => \App\Models\Setting::get('school_phone', ''),
            'school_website' => \App\Models\Setting::get('school_website', ''),
            'school_email' => \App\Models\Setting::get('school_email', ''),
            'site_logo' => \App\Models\Setting::get('site_logo'),
            'site_logo_pemda' => \App\Models\Setting::get('site_logo_pemda'),
        ];

        $schoolCity = \App\Models\Setting::get('school_city', 'Semarang');
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');
        $teacherName = $exam->teacher?->user?->name ?? ($exam->teacher?->full_name ?? '-');
        $teacherNip = $exam->teacher?->nip ?? '-';

        $examDate = $exam->start_time ? \Carbon\Carbon::parse($exam->start_time)->translatedFormat('l, d F Y') : now()->translatedFormat('l, d F Y');
        $examTimeRange = ($exam->start_time ? \Carbon\Carbon::parse($exam->start_time)->format('H:i') : '07:30') . ' - ' . ($exam->end_time ? \Carbon\Carbon::parse($exam->end_time)->format('H:i') : 'Selesai') . ' WIB';

        return view('cbt::pdf.report_berita_acara', [
            'exam' => $exam,
            'bank' => $exam->bank,
            'kop' => $kop,
            'notes' => $notes,
            'total_students' => $totalStudents,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'present_percentage' => $presentPercentage,
            'absent_percentage' => $absentPercentage,
            'present_students' => $presentStudents,
            'absent_students' => $absentStudents,
            'all_student_rows' => $allStudentRows,
            'classroom_names' => $classroomNamesStr,
            'exam_date' => $examDate,
            'exam_time_range' => $examTimeRange,
            'school_city' => $schoolCity,
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
            'teacher_name' => $teacherName,
            'teacher_nip' => $teacherNip,
        ]);
    }

    public function updateNotes(Request $request, $id)
    {
        $exam = CbtExam::findOrFail($id);
        $this->checkExamOwnership($exam);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $exam->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Catatan penyelenggaraan ujian berhasil disimpan.');
    }

    protected function formatStudentExamStatusLabel($stExam): string
    {
        if (!$stExam) return 'Belum Mengerjakan';
        if ($stExam->status === 'submitted' || $stExam->status === 'completed') return 'Hadir (Selesai)';
        if ($stExam->status === 'started') return 'Hadir (Mengerjakan)';
        if ($stExam->status === 'login') return 'Hadir (Login)';
        if ($stExam->status === 'logged_out') return 'Dikeluarkan';
        if ($stExam->status === 'blocked') return 'Diblokir';
        return 'Belum Mulai';
    }

    /**
     * Export / Cetak Bundel Lengkap PDF (Data Jawaban + Matriks Dikotomi + Daftar Nilai).
     */
    public function exportFullReportPdf($id)
    {
        $exam = CbtExam::with(['bank.questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'bank.subject', 'teacher.user', 'classrooms'])->findOrFail($id);
        $this->checkExamOwnership($exam);

        $bank = $exam->bank;
        $questions = $bank ? $bank->questions : collect();

        // Concatenate Answer Keys
        $answerKeysArr = [];
        $maxOptionCount = 5;

        foreach ($questions as $q) {
            $keyChar = '-';
            $correct = $q->correct_answer ?? $q->answer_key;
            if (!empty($correct) || $correct === 0 || $correct === '0') {
                $rawKey = is_array($correct) ? ($correct[0] ?? '-') : $correct;
                if (is_numeric($rawKey)) {
                    $keyChar = chr(65 + (int)$rawKey);
                } else {
                    $keyChar = strtoupper(trim((string)$rawKey));
                }
            } elseif (!empty($q->options)) {
                $opts = is_string($q->options) ? json_decode($q->options, true) : $q->options;
                if (is_array($opts)) {
                    foreach ($opts as $optIdx => $optVal) {
                        if (is_array($optVal) && !empty($optVal['is_correct'])) {
                            $keyChar = !empty($optVal['key']) ? strtoupper((string)$optVal['key']) : chr(65 + (int)$optIdx);
                            break;
                        }
                    }
                }
            }
            $answerKeysArr[] = substr($keyChar, 0, 1);
        }

        $answerKeysString = implode('', $answerKeysArr);

        // Fetch student exams for this specific exam session
        $studentExams = CbtStudentExam::where('cbt_exam_id', $id)
            ->with(['student.classrooms', 'answers'])
            ->get();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];

        $classroomNamesArr = $exam->classrooms ? $exam->classrooms->pluck('name')->toArray() : [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $ansCodeArr = [];
            $dichotomousArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                    $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                    $ansCodeArr[] = $optChar;

                    if ($ans->is_correct === true) {
                        $dichotomousArr[] = '1';
                        $correctCount++;
                    } else {
                        $dichotomousArr[] = '0';
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = 'X';
                    $dichotomousArr[] = '0';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 70;

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'dichotomous_arr' => $dichotomousArr,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        $lulusCount = collect($studentRows)->where('is_tuntas', true)->count();
        $tidakLulusCount = collect($studentRows)->where('is_tuntas', false)->count();

        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $n > 0 ? array_sum($correctsArr) / $n : 0,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $n > 0 ? array_sum($scoresArr) / $n : 0,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
            'lulus' => $lulusCount,
            'tidak_lulus' => $tidakLulusCount,
        ];

        $classroomNamesStr = !empty($classroomNamesArr) ? implode(', ', $classroomNamesArr) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_full_bundle', [
            'exam' => $exam,
            'bank' => $bank,
            'questions' => $questions,
            'answer_keys_string' => $answerKeysString,
            'max_option_count' => $maxOptionCount,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => $exam->start_time ? $exam->start_time->translatedFormat('d F Y') : now()->translatedFormat('d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }
}
