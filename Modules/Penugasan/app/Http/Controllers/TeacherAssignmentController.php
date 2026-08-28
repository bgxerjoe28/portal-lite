<?php

namespace Modules\Penugasan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TeacherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Subject;
use Modules\Akademik\Models\Teacher;
use Modules\Penilaian\Models\GradingComponent;
use Modules\Penilaian\Models\GradingItem;
use Modules\Penilaian\Models\StudentGrade;
use Modules\Penugasan\Models\Assignment;
use Modules\Penugasan\Models\AssignmentQuestion;
use Modules\Penugasan\Models\AssignmentSubmission;
use Modules\Penugasan\Models\AssignmentAnswer;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = Assignment::with(['classroom', 'subject', 'gradingComponent'])
            ->withCount(['questions', 'submissions']);

        if (!$user->hasRole('admin')) {
            $query->where('teacher_id', $user->id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $assignments = $query->orderBy('created_at', 'desc')->get();

        // Get filter options based on teacher schedules
        $user = Auth::user();
        $teacherId = TeacherService::getAuthTeacherId();
        
        $scheduleQuery = Schedule::with(['subject', 'classroom', 'religion'])
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
            $scheduleQuery->where('teacher_id', $teacherId);
        }

        $schedules = $scheduleQuery->get();

        $schedules->each(function ($schedule) {
            /** @var Schedule $schedule */
            if ($schedule->subject && $schedule->religion) {
                $clone = clone $schedule->subject;
                if (!str_contains($clone->name, '(' . $schedule->religion->name . ')')) {
                    $clone->name .= ' (' . $schedule->religion->name . ')';
                }
                $schedule->setRelation('subject', $clone);
            }
        });

        $assignments->each(function ($assignment) {
            $religion = $this->getReligionForAssignment($assignment);
            if ($religion && $assignment->subject) {
                $clone = clone $assignment->subject;
                if (!str_contains($clone->name, '(' . $religion->name . ')')) {
                    $clone->name .= ' (' . $religion->name . ')';
                }
                $assignment->setRelation('subject', $clone);
            }
        });

        $classrooms = $schedules->pluck('classroom')->filter()->unique('id')->values();
        $subjects = $schedules->pluck('subject')->filter()->unique('id')->values();

        return Inertia::render('Penugasan/Guru/Index', [
            'assignments' => $assignments,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'schedules' => $schedules,
            'filters' => $request->only(['classroom_id', 'subject_id']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $teacherId = TeacherService::getAuthTeacherId();

        $scheduleQuery = Schedule::with(['subject', 'classroom', 'religion'])
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
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

        $classrooms = $schedules->pluck('classroom')->filter()->unique('id')->values();
        $subjects = $schedules->pluck('subject')->filter()->unique('id')->values();

        $gradingComponentsQuery = GradingComponent::with('subject')
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
            $gradingComponentsQuery->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                  ->orWhereNull('teacher_id');
            });
        }

        $gradingComponents = $gradingComponentsQuery->get();

        $availablePrerequisites = Assignment::with(['classroom', 'subject'])
            ->where('academic_year_id', $activeYear?->id)
            ->when(!$user->hasRole('admin'), fn ($q) => $q->where('teacher_id', Auth::id()))
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title . ($a->classroom ? ' (' . $a->classroom->name . ')' : ''),
                'classroom_id' => $a->classroom_id,
                'subject_id' => $a->subject_id,
            ]);

        return Inertia::render('Penugasan/Guru/Form', [
            'assignment' => null,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'schedules' => $schedules,
            'gradingComponents' => $gradingComponents,
            'availablePrerequisites' => $availablePrerequisites,
        ]);
    }

    public function store(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $request->validate([
            'type' => 'required|in:standard,performance',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'grading_component_id' => 'nullable|exists:grading_components,id',
            'prerequisite_assignment_id' => 'nullable|exists:assignments,id',
            'start_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:start_at',
            'is_published' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:essay,mcq,performance_indicator,performance_checklist',
            'questions.*.question_text' => 'required|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'nullable|string',
            'questions.*.keywords' => 'nullable',
            'questions.*.allow_url_upload' => 'boolean',
            'questions.*.max_score' => 'required|numeric|min:1',
        ]);

        $classroomIds = $request->classroom_ids ?? ($request->classroom_id ? [$request->classroom_id] : []);
        $classroomIds = array_values(array_unique(array_filter($classroomIds)));

        if (empty($classroomIds)) {
            return back()->withErrors(['classroom_ids' => 'Pilih setidaknya satu kelas target.']);
        }

        $createdCount = 0;
        DB::transaction(function () use ($request, $classroomIds, $activeYear, &$createdCount) {
            foreach ($classroomIds as $classId) {
                $targetPrereqId = $this->resolvePrerequisiteForClassroom(
                    $request->prerequisite_assignment_id,
                    $classId,
                    $activeYear?->id
                );

                $assignment = Assignment::create([
                    'title' => $request->title,
                    'type' => $request->type,
                    'description' => $request->description,
                    'academic_year_id' => $activeYear?->id,
                    'teacher_id' => Auth::id(),
                    'classroom_id' => $classId,
                    'subject_id' => $request->subject_id,
                    'grading_component_id' => $request->grading_component_id,
                    'prerequisite_assignment_id' => $targetPrereqId,
                    'start_at' => $request->start_at,
                    'due_at' => $request->due_at,
                    'is_published' => $request->is_published ?? false,
                ]);

                foreach ($request->questions as $index => $q) {
                    $keywords = null;
                    if (!empty($q['keywords'])) {
                        if (is_array($q['keywords'])) {
                            $keywords = $q['keywords'];
                        } else {
                            $keywords = array_values(array_filter(array_map('trim', explode(',', $q['keywords']))));
                        }
                    }

                    AssignmentQuestion::create([
                        'assignment_id' => $assignment->id,
                        'type' => $q['type'],
                        'question_text' => $q['question_text'],
                        'options' => $q['type'] === 'mcq' ? ($q['options'] ?? []) : null,
                        'correct_answer' => $q['type'] === 'mcq' ? ($q['correct_answer'] ?? null) : null,
                        'keywords' => $q['type'] === 'essay' ? $keywords : null,
                        'allow_url_upload' => $q['type'] === 'essay' ? ($q['allow_url_upload'] ?? false) : false,
                        'max_score' => $q['max_score'] ?? 10,
                        'sort_order' => $index + 1,
                    ]);
                }

                if ($assignment->is_published) {
                    $this->syncGradingItem($assignment);
                }

                $createdCount++;
            }
        });

        $msg = $createdCount > 1 
            ? "Tugas berhasil dibuat untuk {$createdCount} kelas sekaligus." 
            : "Tugas berhasil dibuat.";

        return redirect()->route('guru.assignments.index')->with('success', $msg);
    }

    public function edit($id)
    {
        $assignment = Assignment::with('questions')->findOrFail($id);
        $user = Auth::user();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $teacherId = TeacherService::getAuthTeacherId();

        $scheduleQuery = Schedule::with(['subject', 'classroom', 'religion'])
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
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

        $classrooms = $schedules->pluck('classroom')->filter()->unique('id')->values();
        $subjects = $schedules->pluck('subject')->filter()->unique('id')->values();

        $gradingComponentsQuery = GradingComponent::with('subject')
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
            $gradingComponentsQuery->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                  ->orWhereNull('teacher_id');
            });
        }

        $gradingComponents = $gradingComponentsQuery->get();

        $availablePrerequisites = Assignment::with(['classroom', 'subject'])
            ->where('academic_year_id', $activeYear?->id)
            ->where('id', '!=', $assignment->id)
            ->when(!$user->hasRole('admin'), fn ($q) => $q->where('teacher_id', Auth::id()))
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title . ($a->classroom ? ' (' . $a->classroom->name . ')' : ''),
                'classroom_id' => $a->classroom_id,
                'subject_id' => $a->subject_id,
            ]);

        return Inertia::render('Penugasan/Guru/Form', [
            'assignment' => $assignment,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'schedules' => $schedules,
            'gradingComponents' => $gradingComponents,
            'availablePrerequisites' => $availablePrerequisites,
            'isDuplicate' => false,
        ]);
    }

    public function duplicate($id)
    {
        $assignment = Assignment::with('questions')->findOrFail($id);
        $user = Auth::user();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $teacherId = TeacherService::getAuthTeacherId();

        $scheduleQuery = Schedule::with(['subject', 'classroom', 'religion'])
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
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

        $classrooms = $schedules->pluck('classroom')->filter()->unique('id')->values();
        $subjects = $schedules->pluck('subject')->filter()->unique('id')->values();

        $gradingComponentsQuery = GradingComponent::with('subject')
            ->where('academic_year_id', $activeYear?->id);

        if (!$user->hasRole('admin') && $teacherId) {
            $gradingComponentsQuery->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                  ->orWhereNull('teacher_id');
            });
        }

        $gradingComponents = $gradingComponentsQuery->get();

        $availablePrerequisites = Assignment::with(['classroom', 'subject'])
            ->where('academic_year_id', $activeYear?->id)
            ->where('id', '!=', $assignment->id)
            ->when(!$user->hasRole('admin'), fn ($q) => $q->where('teacher_id', Auth::id()))
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title . ($a->classroom ? ' (' . $a->classroom->name . ')' : ''),
                'classroom_id' => $a->classroom_id,
                'subject_id' => $a->subject_id,
            ]);

        $assignmentData = $assignment->toArray();
        unset($assignmentData['id']);

        return Inertia::render('Penugasan/Guru/Form', [
            'assignment' => $assignmentData,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'schedules' => $schedules,
            'gradingComponents' => $gradingComponents,
            'availablePrerequisites' => $availablePrerequisites,
            'isDuplicate' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'grading_component_id' => 'nullable|exists:grading_components,id',
            'prerequisite_assignment_id' => 'nullable|exists:assignments,id',
            'start_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:start_at',
            'is_published' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.type' => 'required|in:essay,mcq,performance_indicator,performance_checklist',
            'questions.*.question_text' => 'required|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'nullable|string',
            'questions.*.keywords' => 'nullable',
            'questions.*.allow_url_upload' => 'boolean',
            'questions.*.max_score' => 'required|numeric|min:1',
        ]);

        $targetPrereqId = $this->resolvePrerequisiteForClassroom(
            $request->prerequisite_assignment_id,
            $request->classroom_id,
            $assignment->academic_year_id
        );

        $assignment->update([
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'classroom_id' => $request->classroom_id,
            'subject_id' => $request->subject_id,
            'grading_component_id' => $request->grading_component_id,
            'prerequisite_assignment_id' => $targetPrereqId,
            'start_at' => $request->start_at,
            'due_at' => $request->due_at,
            'is_published' => $request->is_published ?? false,
        ]);

        // Sync questions without deleting existing ones if they are kept
        $existingIds = $assignment->questions()->pluck('id')->toArray();
        $submittedIds = collect($request->questions)->pluck('id')->filter()->toArray();

        $removedIds = array_diff($existingIds, $submittedIds);
        if (!empty($removedIds)) {
            $assignment->questions()->whereIn('id', $removedIds)->delete();
        }

        foreach ($request->questions as $index => $q) {
            $keywords = null;
            if (!empty($q['keywords'])) {
                if (is_array($q['keywords'])) {
                    $keywords = $q['keywords'];
                } else {
                    $keywords = array_values(array_filter(array_map('trim', explode(',', $q['keywords']))));
                }
            }

            $questionData = [
                'assignment_id' => $assignment->id,
                'type' => $q['type'],
                'question_text' => $q['question_text'],
                'options' => $q['type'] === 'mcq' ? ($q['options'] ?? []) : null,
                'correct_answer' => $q['type'] === 'mcq' ? ($q['correct_answer'] ?? null) : null,
                'keywords' => $q['type'] === 'essay' ? $keywords : null,
                'allow_url_upload' => $q['type'] === 'essay' ? ($q['allow_url_upload'] ?? false) : false,
                'max_score' => $q['max_score'] ?? 10,
                'sort_order' => $index + 1,
            ];

            if (!empty($q['id']) && in_array($q['id'], $existingIds)) {
                AssignmentQuestion::where('id', $q['id'])
                    ->where('assignment_id', $assignment->id)
                    ->update($questionData);
            } else {
                AssignmentQuestion::create($questionData);
            }
        }

        if ($assignment->is_published) {
            $this->syncGradingItem($assignment);
        }

        return redirect()->route('guru.assignments.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function publish($id)
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->update(['is_published' => !$assignment->is_published]);

        if ($assignment->is_published) {
            $this->syncGradingItem($assignment);
        }

        $msg = $assignment->is_published ? 'Tugas berhasil diposting / diaktifkan.' : 'Tugas dikembalikan ke status Draft.';
        return redirect()->back()->with('success', $msg);
    }

    public function publishGrades($id)
    {
        $assignment = Assignment::with('submissions')->findOrFail($id);
        $assignment->update(['is_grades_published' => !$assignment->is_grades_published]);

        if ($assignment->is_grades_published) {
            $this->syncGradingItem($assignment);
            foreach ($assignment->submissions as $sub) {
                $this->syncStudentGradeToPenilaian($assignment, $sub);
            }
            $msg = 'Nilai tugas berhasil dipublikasikan ke siswa dan disinkronkan ke Penilaian.';
        } else {
            $msg = 'Publikasi nilai tugas ditarik kembali (siswa belum dapat melihat nilai).';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy($id)
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->delete();

        return redirect()->route('guru.assignments.index')->with('success', 'Tugas berhasil dihapus.');
    }

    public function submissions($id)
    {
        $assignment = Assignment::with(['classroom.students', 'subject', 'gradingComponent', 'questions'])
            ->findOrFail($id);

        $religion = $this->getReligionForAssignment($assignment);
        if ($religion && $assignment->subject) {
            $clone = clone $assignment->subject;
            if (!str_contains($clone->name, '(' . $religion->name . ')')) {
                $clone->name .= ' (' . $religion->name . ')';
            }
            $assignment->setRelation('subject', $clone);
        }

        $submissions = AssignmentSubmission::with(['student', 'answers.question'])
            ->where('assignment_id', $id)
            ->get()
            ->keyBy('student_id');

        // Build list for all students in class
        $students = $assignment->classroom->students;
        if ($religion) {
            $students = $students->where('religion_id', $religion->id);
        }

        // Count frequencies for duplicate checking and assign group IDs
        $textFreq = [];
        $urlFreq = [];
        $duplicateGroups = [];
        $groupIdCounter = 1;
        
        foreach ($submissions as $sub) {
            foreach ($sub->answers as $ans) {
                if ($ans->question && $ans->question->type === 'essay') {
                    if (!empty(trim($ans->answer_text))) {
                        $key = 'text|' . $ans->question_id . '|' . strtolower(trim($ans->answer_text));
                        $textFreq[$key] = ($textFreq[$key] ?? 0) + 1;
                    }
                    if (!empty(trim($ans->url_upload))) {
                        $key = 'url|' . $ans->question_id . '|' . strtolower(trim($ans->url_upload));
                        $urlFreq[$key] = ($urlFreq[$key] ?? 0) + 1;
                    }
                }
            }
        }

        // Assign group IDs for duplicates
        foreach ($textFreq as $key => $count) {
            if ($count > 1 && !isset($duplicateGroups[$key])) {
                $duplicateGroups[$key] = $groupIdCounter++;
            }
        }
        foreach ($urlFreq as $key => $count) {
            if ($count > 1 && !isset($duplicateGroups[$key])) {
                $duplicateGroups[$key] = $groupIdCounter++;
            }
        }

        $studentsList = $students->map(function ($student) use ($submissions, $textFreq, $urlFreq, $duplicateGroups) {
            $submission = $submissions->get($student->id);
            
            $hasDuplicate = false;
            $answersList = [];
            $studentDuplicateGroups = [];
            
            if ($submission) {
                $answersList = $submission->answers->map(function ($ans) use ($textFreq, $urlFreq, $duplicateGroups, &$hasDuplicate, &$studentDuplicateGroups) {
                    $isDupText = false;
                    $isDupUrl = false;
                    $groupId = null;
                    
                    if ($ans->question && $ans->question->type === 'essay') {
                        if (!empty(trim($ans->answer_text))) {
                            $key = 'text|' . $ans->question_id . '|' . strtolower(trim($ans->answer_text));
                            if (($textFreq[$key] ?? 0) > 1) {
                                $isDupText = true;
                                $groupId = $duplicateGroups[$key] ?? null;
                            }
                        }
                        if (!empty(trim($ans->url_upload))) {
                            $key = 'url|' . $ans->question_id . '|' . strtolower(trim($ans->url_upload));
                            if (($urlFreq[$key] ?? 0) > 1) {
                                $isDupUrl = true;
                                $groupId = $duplicateGroups[$key] ?? null;
                            }
                        }
                    }
                    
                    if ($isDupText || $isDupUrl) {
                        $hasDuplicate = true;
                        if ($groupId) {
                            $studentDuplicateGroups[] = $groupId;
                        }
                    }

                    return [
                        'id' => $ans->id,
                        'question_id' => $ans->question_id,
                        'answer_text' => $ans->answer_text,
                        'url_upload' => $ans->url_upload,
                        'is_duplicate_text' => $isDupText,
                        'is_duplicate_url' => $isDupUrl,
                        'duplicate_group_id' => $groupId,
                        'similarity_percentage' => $ans->similarity_percentage,
                        'score' => $ans->score,
                        'is_correct' => $ans->is_correct,
                        'feedback' => $ans->feedback,
                        'question' => $ans->question,
                    ];
                });
            }
            
            $studentDuplicateGroups = array_values(array_unique($studentDuplicateGroups));
            sort($studentDuplicateGroups);

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'nisn' => $student->nisn,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'status' => $submission->status,
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('d M Y H:i') : null,
                    'total_score' => $submission->total_score,
                    'teacher_notes' => $submission->teacher_notes,
                    'is_editable' => (bool) $submission->is_editable,
                    'has_duplicate' => $hasDuplicate,
                    'duplicate_groups' => $studentDuplicateGroups,
                    'answers' => $answersList,
                ] : null,
            ];
        })->values();

        return Inertia::render('Penugasan/Guru/Submissions', [
            'assignment' => $assignment,
            'studentsList' => $studentsList,
        ]);
    }

    public function gradeSubmission(Request $request, $assignmentId, $submissionId)
    {
        $assignment = Assignment::with('questions')->findOrFail($assignmentId);
        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)->findOrFail($submissionId);

        $request->validate([
            'answers' => 'required|array',
            'answers.*.id' => 'required|exists:assignment_answers,id',
            'answers.*.score' => 'required|numeric|min:0',
            'answers.*.feedback' => 'nullable|string',
            'teacher_notes' => 'nullable|string',
        ]);

        foreach ($request->answers as $ansData) {
            $ans = AssignmentAnswer::where('submission_id', $submission->id)->find($ansData['id']);
            if ($ans) {
                $ans->update([
                    'score' => $ansData['score'],
                    'feedback' => $ansData['feedback'] ?? null,
                ]);
            }
        }

        // Recalculate scaled total score (max 100)
        $totalMaxRaw = $assignment->questions->sum('max_score');
        $studentRawScore = AssignmentAnswer::where('submission_id', $submission->id)->sum('score');

        $scaledScore = $totalMaxRaw > 0 ? round(($studentRawScore / $totalMaxRaw) * 100, 2) : 0;
        $scaledScore = min(100, max(0, $scaledScore));

        $submission->update([
            'total_score' => $scaledScore,
            'status' => 'graded',
            'teacher_notes' => $request->teacher_notes,
        ]);

        // Sync to Penilaian Grade Table only if grades are published
        if ($assignment->is_grades_published) {
            $this->syncStudentGradeToPenilaian($assignment, $submission);
            $msg = 'Penilaian siswa berhasil disimpan dan disinkronkan ke tabel penilaian guru.';
        } else {
            $msg = 'Penilaian siswa berhasil disimpan (belum dipublikasikan ke siswa).';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function toggleEditSubmission(Request $request, $id, $submissionId)
    {
        $submission = AssignmentSubmission::where('assignment_id', $id)->findOrFail($submissionId);

        $newStatus = !$submission->is_editable;
        $submission->update([
            'is_editable' => $newStatus,
        ]);

        $msg = $newStatus
            ? "Akses edit untuk siswa {$submission->student->full_name} berhasil dibuka."
            : "Akses edit untuk siswa {$submission->student->full_name} ditutup.";

        return redirect()->back()->with('success', $msg);
    }

    public function rejectSubmission(Request $request, $id, $submissionId)
    {
        $submission = AssignmentSubmission::where('assignment_id', $id)->findOrFail($submissionId);

        $request->validate([
            'teacher_notes' => 'nullable|string',
        ]);

        $defaultNote = 'Ajuan jawaban Anda ditolak oleh guru (perlu perbaikan). Silakan periksa kembali dan kumpulkan ulang.';
        $notes = $request->teacher_notes ?: $defaultNote;

        $submission->update([
            'is_editable' => true,
            'status' => 'submitted',
            'total_score' => 0,
            'teacher_notes' => $notes,
        ]);

        return redirect()->back()->with('success', "Ajuan tugas siswa {$submission->student->full_name} berhasil ditolak dan akses edit telah dibuka.");
    }

    public function unlockAllEdit(Request $request, $id)
    {
        Assignment::findOrFail($id);

        AssignmentSubmission::where('assignment_id', $id)
            ->update(['is_editable' => true]);

        return redirect()->back()->with('success', 'Akses edit berhasil dibuka untuk semua siswa yang sudah mengumpulkan.');
    }

    public function forceSubmitSubmission(Request $request, $id, $submissionId)
    {
        $assignment = Assignment::with(['questions'])->findOrFail($id);
        $submission = AssignmentSubmission::with(['answers', 'student'])
            ->where('assignment_id', $id)
            ->where('id', $submissionId)
            ->firstOrFail();

        if ($submission->status !== 'draft') {
            return redirect()->back()->with('error', 'Tugas ini sudah dikumpulkan atau dinilai sebelumnya.');
        }

        $this->processSubmissionScoring($assignment, $submission, true);

        return redirect()->back()->with('success', "Draf jawaban siswa ({$submission->student?->full_name}) berhasil dipaksa kumpulkan dan dinilai.");
    }

    public function forceSubmitAll(Request $request, $id)
    {
        $assignment = Assignment::with(['questions'])->findOrFail($id);
        $draftSubmissions = AssignmentSubmission::with(['answers', 'student'])
            ->where('assignment_id', $id)
            ->where('status', 'draft')
            ->get();

        if ($draftSubmissions->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada draf jawaban siswa yang dapat dikumpulkan.');
        }

        foreach ($draftSubmissions as $submission) {
            $this->processSubmissionScoring($assignment, $submission, true);
        }

        return redirect()->back()->with('success', "Berhasil memaksa pengumpulan {$draftSubmissions->count()} draf jawaban siswa.");
    }

    protected function processSubmissionScoring(Assignment $assignment, AssignmentSubmission $submission, bool $isForceSubmit = false)
    {
        $now = now();
        $studentRawScore = 0;
        $totalMaxRawScore = $assignment->questions->sum('max_score');

        foreach ($assignment->questions as $question) {
            $userAns = $submission->answers->firstWhere('question_id', $question->id);
            $answerText = $userAns?->answer_text ?? null;
            $urlUpload = $userAns?->url_upload ?? null;

            $score = 0;
            $isCorrect = null;
            $similarityPercentage = null;

            if ($question->type === 'mcq') {
                if (!empty($answerText) && !empty($question->correct_answer)) {
                    $isCorrect = (trim(strtoupper($answerText)) === trim(strtoupper($question->correct_answer)));
                    $score = $isCorrect ? $question->max_score : 0;
                }
            } elseif ($question->type === 'essay') {
                if (!empty($answerText) && !empty($question->keywords)) {
                    $similarityPercentage = $this->calculateSimilarityPercentage($answerText, $question->keywords);
                    $score = round(($similarityPercentage / 100) * $question->max_score, 2);
                }
            }

            $studentRawScore += $score;

            if ($userAns) {
                $userAns->update([
                    'similarity_percentage' => $similarityPercentage,
                    'score' => $score,
                    'is_correct' => $isCorrect,
                ]);
            } else {
                AssignmentAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'answer_text' => null,
                    'url_upload' => null,
                    'similarity_percentage' => null,
                    'score' => 0,
                    'is_correct' => false,
                ]);
            }
        }

        $scaledScore = $totalMaxRawScore > 0 ? round(($studentRawScore / $totalMaxRawScore) * 100, 2) : 0;
        $scaledScore = min(100, max(0, $scaledScore));

        $notes = $submission->teacher_notes;
        if ($isForceSubmit) {
            $notes = $notes ? $notes . ' (Dipaksa kumpul oleh guru)' : 'Jawaban dipaksa kumpulkan oleh guru setelah batas waktu.';
        }

        /** @var AssignmentSubmission $submission */
        $submission->update([
            'status' => 'submitted',
            'submitted_at' => $now,
            'total_score' => $scaledScore,
            'teacher_notes' => $notes,
            'is_editable' => false,
        ]);

        if ($assignment->grading_item_id) {
            StudentGrade::updateOrCreate(
                [
                    'grading_item_id' => $assignment->grading_item_id,
                    'student_id' => $submission->student_id,
                ],
                [
                    'score' => $scaledScore,
                    'note' => 'Penugasan: ' . $assignment->title . ($isForceSubmit ? ' (Force Submit)' : ''),
                ]
            );
        }
    }

    protected function calculateSimilarityPercentage(string $studentAnswer, $keywords): float
    {
        $studentTextClean = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $studentAnswer));
        $studentWords = array_values(array_filter(explode(' ', $studentTextClean)));

        if (empty($studentWords)) {
            return 0.0;
        }

        $keywordList = [];
        if (is_array($keywords)) {
            $keywordList = $keywords;
        } else {
            $keywordList = array_filter(array_map('trim', explode(',', strtolower((string)$keywords))));
        }

        if (empty($keywordList)) {
            return 0.0;
        }

        $matchedCount = 0;
        $totalKeywords = count($keywordList);

        foreach ($keywordList as $kw) {
            $kwClean = strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $kw)));
            if (empty($kwClean)) continue;

            if (str_contains($studentTextClean, $kwClean)) {
                $matchedCount++;
            }
        }

        $percentage = round(($matchedCount / max(1, $totalKeywords)) * 100, 2);
        return min(100.0, max(0.0, $percentage));
    }

    public function performanceGrading($id)
    {
        $assignment = Assignment::with(['classroom.students', 'subject', 'gradingComponent', 'questions'])
            ->findOrFail($id);
            
        $religion = $this->getReligionForAssignment($assignment);
        if ($religion && $assignment->subject) {
            $clone = clone $assignment->subject;
            if (!str_contains($clone->name, '(' . $religion->name . ')')) {
                $clone->name .= ' (' . $religion->name . ')';
            }
            $assignment->setRelation('subject', $clone);
        }

        if ($assignment->type !== 'performance') {
            return redirect()->route('guru.assignments.index')->with('error', 'Tugas ini bukan tipe Penilaian Kinerja.');
        }

        $submissions = AssignmentSubmission::with(['answers'])
            ->where('assignment_id', $id)
            ->get()
            ->keyBy('student_id');

        // Build list for all students in class
        $students = $assignment->classroom->students;
        if ($religion) {
            $students = $students->where('religion_id', $religion->id);
        }

        $studentsList = $students->map(function ($student) use ($submissions, $assignment) {
            $submission = $submissions->get($student->id);
            $answers = [];
            
            // Map existing answers by question_id
            $existingAnswers = [];
            if ($submission) {
                foreach ($submission->answers as $ans) {
                    $existingAnswers[$ans->question_id] = $ans->score;
                }
            }

            // Create default answers array for frontend
            foreach ($assignment->questions as $q) {
                $answers[] = [
                    'question_id' => $q->id,
                    'score' => $existingAnswers[$q->id] ?? 0
                ];
            }

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'nisn' => $student->nisn,
                'submission_id' => $submission ? $submission->id : null,
                'total_score' => $submission ? $submission->total_score : 0,
                'teacher_notes' => $submission ? $submission->teacher_notes : '',
                'answers' => $answers,
            ];
        })->values();

        return Inertia::render('Penugasan/Guru/PerformanceGrading', [
            'assignment' => $assignment,
            'studentsList' => $studentsList,
        ]);
    }

    public function storePerformanceGrading(Request $request, $id)
    {
        $assignment = Assignment::with('questions')->findOrFail($id);

        $request->validate([
            'gradings' => 'required|array',
            'gradings.*.student_id' => 'required|exists:students,id',
            'gradings.*.answers' => 'required|array',
            'gradings.*.answers.*.question_id' => 'required|exists:assignment_questions,id',
            'gradings.*.answers.*.score' => 'required|numeric|min:0',
        ]);

        $totalMaxRaw = $assignment->questions->sum('max_score');

        foreach ($request->gradings as $gradingData) {
            $studentId = $gradingData['student_id'];
            
            // Get or create submission
            $submission = AssignmentSubmission::firstOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'student_id' => $studentId,
                ],
                [
                    'status' => 'graded',
                    'submitted_at' => now(),
                    'total_score' => 0,
                ]
            );

            // Force status to graded
            $submission->status = 'graded';

            $studentRawScore = 0;

            // Process answers
            foreach ($gradingData['answers'] as $ansData) {
                $qId = $ansData['question_id'];
                $score = $ansData['score'];

                AssignmentAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'question_id' => $qId,
                    ],
                    [
                        'score' => $score,
                    ]
                );
                $studentRawScore += $score;
            }

            $scaledScore = $totalMaxRaw > 0 ? round(($studentRawScore / $totalMaxRaw) * 100, 2) : 0;
            $scaledScore = min(100, max(0, $scaledScore));

            $submission->total_score = $scaledScore;
            $submission->save();

            // Sync to Penilaian Grade Table only if grades are published
            if ($assignment->is_grades_published) {
                $this->syncStudentGradeToPenilaian($assignment, $submission);
            }
        }

        $msg = 'Rekap Nilai Kinerja berhasil disimpan.';
        if ($assignment->is_grades_published) {
            $msg .= ' Nilai tersinkronisasi ke menu Penilaian.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Helper to sync GradingItem in Penilaian module
     */
    protected function syncGradingItem(Assignment $assignment)
    {
        if (!$assignment->grading_component_id || !$assignment->classroom_id) {
            return;
        }

        $gradingItem = null;
        if ($assignment->grading_item_id) {
            $gradingItem = GradingItem::find($assignment->grading_item_id);
        }

        if (!$gradingItem) {
            $gradingItem = GradingItem::create([
                'grading_component_id' => $assignment->grading_component_id,
                'classroom_id' => $assignment->classroom_id,
                'title' => $assignment->title,
                'date' => $assignment->start_at ? $assignment->start_at->format('Y-m-d') : date('Y-m-d'),
            ]);

            $assignment->update(['grading_item_id' => $gradingItem->id]);
        } else {
            $gradingItem->update([
                'grading_component_id' => $assignment->grading_component_id,
                'classroom_id' => $assignment->classroom_id,
                'title' => $assignment->title,
            ]);
        }

        // Sync existing submissions to StudentGrade if grades published
        if ($assignment->is_grades_published) {
            $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)->get();
            foreach ($submissions as $sub) {
                $this->syncStudentGradeToPenilaian($assignment, $sub);
            }
        }
    }

    /**
     * Helper to sync individual StudentGrade in Penilaian module
     */
    protected function syncStudentGradeToPenilaian(Assignment $assignment, AssignmentSubmission $submission)
    {
        if (!$assignment->grading_item_id) {
            $this->syncGradingItem($assignment);
        }

        if ($assignment->grading_item_id) {
            StudentGrade::updateOrCreate(
                [
                    'grading_item_id' => $assignment->grading_item_id,
                    'student_id' => $submission->student_id,
                ],
                [
                    'score' => $submission->total_score,
                    'note' => 'Penugasan: ' . $assignment->title,
                ]
            );
        }
    }

    /**
     * Upload gambar soal ke storage server
     * POST /teacher/assignments/upload-image
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // max 5MB
        ]);

        $file = $request->file('image');
        $ext  = $file->getClientOriginalExtension();

        // Nama file unik: sha256(uuid + user_id + microtime) — tidak bisa tertimpa
        $hash     = hash('sha256', \Illuminate\Support\Str::uuid() . Auth::id() . microtime());
        $filename = $hash . '.' . $ext;

        $path = $file->storeAs('assignments/images', $filename, 'public');
        $url  = \Illuminate\Support\Facades\Storage::url($path);

        return response()->json([
            'url'  => $url,
            'path' => $path,
        ]);
    }

    private function getTeacherIdFromUserId($userId)
    {
        $teacher = Teacher::where('user_id', $userId)->first();
        return $teacher ? $teacher->id : $userId;
    }

    private function getReligionForAssignment($assignment)
    {
        if (!$assignment->subject || !$assignment->subject->is_religion) {
            return null;
        }

        $teacherId = $this->getTeacherIdFromUserId($assignment->teacher_id);

        $schedule = Schedule::with('religion')
            ->where('subject_id', $assignment->subject_id)
            ->where('classroom_id', $assignment->classroom_id)
            ->where('teacher_id', $teacherId)
            ->whereNotNull('religion_id')
            ->first();

        if ($schedule && $schedule->religion) {
            return $schedule->religion;
        }

        // Fallback match without teacher_id if not explicitly found
        $schedule = Schedule::with('religion')
            ->where('subject_id', $assignment->subject_id)
            ->where('classroom_id', $assignment->classroom_id)
            ->whereNotNull('religion_id')
            ->first();

        return $schedule?->religion;
    }

    /**
     * Secara pintar mencarikan ID tugas prasyarat yang sesuai untuk kelas target.
     * Mengatasi kendala pemilihan prasyarat dari kelas lain saat membuat/update tugas multikelas.
     */
    private function resolvePrerequisiteForClassroom($prerequisiteId, $targetClassroomId, $academicYearId)
    {
        if (!$prerequisiteId) {
            return null;
        }

        $selectedPrereq = Assignment::find($prerequisiteId);
        if (!$selectedPrereq) {
            return null;
        }

        // Jika prasyarat sudah berada di kelas yang sama, langsung gunakan ID tersebut
        if ($selectedPrereq->classroom_id == $targetClassroomId) {
            return $selectedPrereq->id;
        }

        // Jika berbeda kelas, cari tugas dengan judul, tipe, dan mata pelajaran yang sama di kelas target
        $matchingPrereq = Assignment::where('classroom_id', $targetClassroomId)
            ->where('subject_id', $selectedPrereq->subject_id)
            ->where('type', $selectedPrereq->type)
            ->where('title', $selectedPrereq->title)
            ->where('id', '!=', $selectedPrereq->id)
            ->where('academic_year_id', $academicYearId)
            ->first();

        return $matchingPrereq ? $matchingPrereq->id : $selectedPrereq->id;
    }
}

