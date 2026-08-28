<?php

namespace Modules\Penugasan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Penugasan\Models\Assignment;
use Modules\Penugasan\Models\AssignmentQuestion;
use Modules\Penugasan\Models\AssignmentSubmission;
use Modules\Penugasan\Models\AssignmentAnswer;
use Modules\Penilaian\Models\StudentGrade;
use Modules\Penilaian\Models\GradingItem;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Teacher;

class StudentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user()->load('student');
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        $classroom = $student->classrooms()->where('classrooms.academic_year_id', $activeYear?->id)->first() ?? $student->classrooms->last();

        if (!$classroom) {
            return Inertia::render('Penugasan/Siswa/Index', [
                'assignments' => [],
                'pendingCount' => 0,
            ]);
        }

        $assignments = Assignment::with(['subject', 'teacher', 'classroom', 'prerequisite'])
            ->where('classroom_id', $classroom->id)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $schedules = Schedule::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $activeYear?->id)
            ->whereNotNull('religion_id')
            ->get();

        $assignments = $assignments->filter(function ($assignment) use ($student, $schedules) {
            if ($assignment->subject && $assignment->subject->is_religion) {
                $teacherId = Teacher::where('user_id', $assignment->teacher_id)->value('id') ?? $assignment->teacher_id;

                $relSchedule = $schedules->first(function ($s) use ($assignment, $teacherId) {
                    return $s->subject_id == $assignment->subject_id && $s->teacher_id == $teacherId;
                }) ?? $schedules->first(function ($s) use ($assignment) {
                    return $s->subject_id == $assignment->subject_id;
                });

                if ($relSchedule && $relSchedule->religion_id) {
                    if ($student->religion_id != $relSchedule->religion_id) {
                        return false;
                    }
                }
            }
            return true;
        });

        $submissions = AssignmentSubmission::whereIn('assignment_id', $assignments->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('assignment_id');

        $allStudentSubmissionIds = AssignmentSubmission::where('student_id', $student->id)
            ->where('status', '!=', 'draft')
            ->pluck('assignment_id')
            ->toArray();

        $formattedAssignments = $assignments->map(function ($assignment) use ($submissions, $allStudentSubmissionIds) {
            $sub = $submissions->get($assignment->id);
            $isDraft = $sub?->status === 'draft';
            $isSubmitted = $sub && $sub->status !== 'draft';

            $isLocked = false;
            $prerequisiteTitle = null;
            if ($assignment->prerequisite_assignment_id) {
                if (!in_array($assignment->prerequisite_assignment_id, $allStudentSubmissionIds)) {
                    $isLocked = true;
                    $prerequisiteTitle = $assignment->prerequisite?->title ?? 'Tugas Sebelumnya';
                }
            }

            $status = 'pending';
            if ($isLocked) {
                $status = 'locked';
            } elseif ($isDraft) {
                $status = 'draft';
            } elseif ($isSubmitted) {
                if ($sub->is_editable) {
                    $status = 'editable';
                } elseif ($assignment->is_grades_published) {
                    $status = 'graded';
                } else {
                    $status = 'submitted';
                }
            }

            return [
                'id' => $assignment->id,
                'type' => $assignment->type,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'subject_name' => $assignment->subject?->name,
                'teacher_name' => $assignment->teacher?->name,
                'start_at' => $assignment->start_at ? $assignment->start_at->format('d M Y H:i') : null,
                'due_at' => $assignment->due_at ? $assignment->due_at->format('d M Y H:i') : null,
                'is_grades_published' => (bool) $assignment->is_grades_published,
                'has_submitted' => $isSubmitted && !$sub->is_editable,
                'submitted_at' => ($isSubmitted && $sub?->submitted_at) ? $sub->submitted_at->format('d M Y H:i') : null,
                'last_saved_at' => $sub?->updated_at ? $sub->updated_at->format('d M Y H:i') : null,
                'total_score' => ($assignment->is_grades_published && $isSubmitted) ? $sub?->total_score : null,
                'status' => $status,
                'is_draft' => $isDraft,
                'is_editable' => (bool) $sub?->is_editable,
                'is_locked' => $isLocked,
                'prerequisite_id' => $assignment->prerequisite_assignment_id,
                'prerequisite_title' => $prerequisiteTitle,
            ];
        });

        $pendingCount = $formattedAssignments->where('has_submitted', false)->where('is_locked', false)->count();

        return Inertia::render('Penugasan/Siswa/Index', [
            'assignments' => $formattedAssignments->values(),
            'pendingCount' => $pendingCount,
        ]);
    }

    public function show($id)
    {
        $user = Auth::user()->load('student');
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
        }

        $assignment = Assignment::with(['subject', 'teacher', 'classroom', 'questions', 'prerequisite'])
            ->where('is_published', true)
            ->findOrFail($id);

        if ($accessError = $this->validateStudentAssignmentAccess($student, $assignment)) {
            return $accessError;
        }

        $submission = AssignmentSubmission::with(['answers.question'])
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        // Check window status
        $now = now();
        $isOpen = $now->greaterThanOrEqualTo($assignment->start_at) && $now->lessThanOrEqualTo($assignment->due_at);
        $isClosed = $now->greaterThan($assignment->due_at);
        $isDraft = $submission && $submission->status === 'draft';

        if ($submission && !$assignment->is_grades_published) {
            $submission->total_score = null;
            $submission->teacher_notes = null;
            if ($submission->answers) {
                foreach ($submission->answers as $ans) {
                    $ans->score = null;
                    $ans->feedback = null;
                }
            }
        }

        return Inertia::render('Penugasan/Siswa/Show', [
            'assignment' => $assignment,
            'submission' => $submission,
            'isOpen' => $isOpen,
            'isClosed' => $isClosed,
            'isDraft' => $isDraft,
            'isEditable' => (bool) $submission?->is_editable,
            'student' => $student,
        ]);
    }

    public function saveDraft(Request $request, $id)
    {
        $user = Auth::user()->load('student');
        $student = $user->student;

        if (!$student) {
            return redirect()->back()->with('error', 'Profil siswa tidak ditemukan.');
        }

        $assignment = Assignment::with(['subject', 'teacher', 'classroom', 'questions', 'prerequisite'])
            ->where('is_published', true)
            ->findOrFail($id);

        if ($accessError = $this->validateStudentAssignmentAccess($student, $assignment)) {
            return $accessError;
        }

        if ($assignment->type === 'performance') {
            return redirect()->back()->with('error', 'Penugasan tipe penilaian kinerja tidak memerlukan pengumpulan jawaban oleh siswa.');
        }

        $now = now();
        $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($now->lessThan($assignment->start_at)) {
            return redirect()->back()->with('error', 'Tugas ini belum dibuka.');
        }

        if ($now->greaterThan($assignment->due_at) && !($existingSubmission && $existingSubmission->is_editable)) {
            return redirect()->back()->with('error', 'Waktu pengerjaan tugas telah ditutup.');
        }

        if ($existingSubmission && $existingSubmission->status !== 'draft' && !$existingSubmission->is_editable) {
            return redirect()->back()->with('error', 'Jawaban Anda sudah dikumpulkan dan tidak dapat diedit sebelum akses dibuka oleh guru.');
        }

        $request->validate([
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|exists:assignment_questions,id',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.url_upload' => 'nullable|url|max:1000',
        ]);

        if ($existingSubmission) {
            /** @var AssignmentSubmission $existingSubmission */
            $existingSubmission->update([
                'status' => 'draft',
                'submitted_at' => null,
                'is_editable' => true,
            ]);
            $submission = $existingSubmission;
        } else {
            $submission = AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'status' => 'draft',
                'submitted_at' => null,
                'total_score' => 0,
                'is_editable' => true,
            ]);
        }

        if ($request->answers) {
            foreach ($request->answers as $ans) {
                if (empty($ans['question_id'])) continue;

                $answerText = $ans['answer_text'] ?? null;
                $urlUpload = $ans['url_upload'] ?? null;

                AssignmentAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'question_id' => $ans['question_id'],
                    ],
                    [
                        'answer_text' => $answerText,
                        'url_upload' => $urlUpload,
                        'similarity_percentage' => null,
                        'score' => null,
                        'is_correct' => null,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Draf jawaban berhasil disimpan. Anda dapat melanjutkan pengerjaan kapan saja sebelum batas waktu.');
    }

    public function submit(Request $request, $id)
    {
        $user = Auth::user()->load('student');
        $student = $user->student;

        if (!$student) {
            return redirect()->back()->with('error', 'Profil siswa tidak ditemukan.');
        }

        $assignment = Assignment::with(['subject', 'teacher', 'classroom', 'questions', 'prerequisite'])
            ->where('is_published', true)
            ->findOrFail($id);

        if ($accessError = $this->validateStudentAssignmentAccess($student, $assignment)) {
            return $accessError;
        }

        if ($assignment->type === 'performance') {
            return redirect()->back()->with('error', 'Penugasan tipe penilaian kinerja tidak memerlukan pengumpulan jawaban oleh siswa.');
        }

        $now = now();
        $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($now->lessThan($assignment->start_at)) {
            return redirect()->back()->with('error', 'Tugas ini belum dibuka.');
        }

        if ($now->greaterThan($assignment->due_at) && !($existingSubmission && $existingSubmission->is_editable)) {
            return redirect()->back()->with('error', 'Waktu pengumpulan tugas telah ditutup.');
        }

        if ($existingSubmission && $existingSubmission->status !== 'draft' && !$existingSubmission->is_editable) {
            return redirect()->back()->with('error', 'Jawaban Anda sudah dikumpulkan dan tidak dapat diedit sebelum akses dibuka oleh guru.');
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:assignment_questions,id',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.url_upload' => 'nullable|url|max:1000',
        ]);

        if ($existingSubmission) {
            /** @var AssignmentSubmission $existingSubmission */
            $existingSubmission->update([
                'submitted_at' => $now,
                'status' => 'submitted',
                'is_editable' => false,
            ]);
            $submission = $existingSubmission;
        } else {
            $submission = AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'submitted_at' => $now,
                'status' => 'submitted',
                'total_score' => 0,
                'is_editable' => false,
            ]);
        }

        $studentRawScore = 0;
        $totalMaxRawScore = $assignment->questions->sum('max_score');

        foreach ($assignment->questions as $question) {
            $userAns = collect($request->answers)->firstWhere('question_id', $question->id);
            $answerText = $userAns['answer_text'] ?? null;
            $urlUpload = $userAns['url_upload'] ?? null;

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

            AssignmentAnswer::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                ],
                [
                    'answer_text' => $answerText,
                    'url_upload' => $urlUpload,
                    'similarity_percentage' => $similarityPercentage,
                    'score' => $score,
                    'is_correct' => $isCorrect,
                ]
            );
        }

        // Scaled score to max 100
        $scaledScore = $totalMaxRawScore > 0 ? round(($studentRawScore / $totalMaxRawScore) * 100, 2) : 0;
        $scaledScore = min(100, max(0, $scaledScore));

        /** @var AssignmentSubmission $submission */
        $submission->update([
            'total_score' => $scaledScore,
            'is_editable' => false,
        ]);

        // Auto sync to Penilaian grade table
        if ($assignment->grading_item_id) {
            StudentGrade::updateOrCreate(
                [
                    'grading_item_id' => $assignment->grading_item_id,
                    'student_id' => $student->id,
                ],
                [
                    'score' => $scaledScore,
                    'note' => 'Penugasan: ' . $assignment->title,
                ]
            );
        }

        return redirect()->back()->with('success', 'Tugas berhasil dikumpulkan.');
    }

    /**
     * Calculate similarity percentage of essay answer against teacher keywords/reference.
     */
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

            // Check if keyword term or phrase is present in student answer
            if (str_contains($studentTextClean, $kwClean)) {
                $matchedCount++;
            }
        }

        $percentage = round(($matchedCount / max(1, $totalKeywords)) * 100, 2);
        return min(100.0, max(0.0, $percentage));
    }

    private function validateStudentAssignmentAccess($student, $assignment)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $classroom = $student->classrooms()->where('classrooms.academic_year_id', $activeYear?->id)->first() ?? $student->classrooms->last();

        if (!$classroom || $assignment->classroom_id !== $classroom->id) {
            return redirect()->route('student.assignments.index')
                ->with('error', 'Anda tidak memiliki akses ke tugas dari kelas lain.');
        }

        if ($assignment->prerequisite_assignment_id) {
            $hasSubmittedPrereq = AssignmentSubmission::where('assignment_id', $assignment->prerequisite_assignment_id)
                ->where('student_id', $student->id)
                ->where('status', '!=', 'draft')
                ->exists();

            if (!$hasSubmittedPrereq) {
                $prereqTitle = $assignment->prerequisite?->title ?? 'Tugas Sebelumnya';
                return redirect()->route('student.assignments.index')
                    ->with('error', "Tugas '{$assignment->title}' masih terkunci. Anda wajib menyelesaikan '{$prereqTitle}' terlebih dahulu.");
            }
        }

        if ($assignment->subject && $assignment->subject->is_religion) {
            $teacherId = Teacher::where('user_id', $assignment->teacher_id)->value('id') ?? $assignment->teacher_id;

            $relSchedule = Schedule::where('classroom_id', $assignment->classroom_id)
                ->where('subject_id', $assignment->subject_id)
                ->where('academic_year_id', $activeYear?->id)
                ->whereNotNull('religion_id')
                ->where(function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId)->orWhereNull('teacher_id');
                })
                ->first();

            if ($relSchedule && $relSchedule->religion_id) {
                if ($student->religion_id != $relSchedule->religion_id) {
                    return redirect()->route('student.assignments.index')->with('error', 'Tugas ini bukan untuk kelompok agama Anda.');
                }
            }
        }

        return null;
    }
}

