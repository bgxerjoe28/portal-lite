<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\TkaSubject;
use Modules\Akademik\Models\TkaStudent;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use App\Models\Setting;

class TkaStudentController extends Controller
{
    public function index(Request $request, TkaSubject $tka_subject)
    {
        $students = TkaStudent::with('student.classroom')
            ->where('tka_subject_id', $tka_subject->id)
            ->latest()
            ->get();

        return Inertia::render('Akademik/Tka/Students/Index', [
            'subject' => $tka_subject,
            'students' => $students
        ]);
    }

    public function store(Request $request, TkaSubject $tka_subject)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::with('classroom')->findOrFail($validated['student_id']);

        // Strict Validation: Only Class XII
        if (!$student->classroom || strpos($student->classroom->name, 'XII') === false) {
            return back()->with('error', 'Gagal mendaftarkan siswa. Mapel TKA hanya diperuntukkan bagi siswa Kelas XII.');
        }

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // Prevent duplicate registration
        $exists = TkaStudent::where('tka_subject_id', $tka_subject->id)
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Siswa sudah terdaftar di Mapel TKA ini.');
        }

        // Check maximum 2 subjects
        $totalChosen = TkaStudent::where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->count();

        if ($totalChosen >= 2) {
            return back()->with('error', 'Gagal mendaftarkan siswa. Siswa sudah memiliki 2 mata pelajaran TKA (Batas Maksimal).');
        }

        TkaStudent::create([
            'tka_subject_id' => $tka_subject->id,
            'student_id' => $student->id,
            'academic_year_id' => $activeYear->id,
            'status' => 'active',
        ]);

        return back()->with('success', 'Siswa berhasil didaftarkan ke Mapel TKA.');
    }

    public function destroy(TkaSubject $tka_subject, TkaStudent $tka_student)
    {
        if ($tka_student->tka_subject_id !== $tka_subject->id) {
            abort(403);
        }

        $tka_student->delete();

        return back()->with('success', 'Siswa berhasil dihapus dari Mapel TKA.');
    }

    public function selfRegister(Request $request)
    {
        if (!$request->user() || !$request->user()->hasRole('siswa')) {
            abort(403, 'Akses ditolak.');
        }

        $enabled = Setting::get('tka_registration_active', '0') === '1';
        if (!$enabled) {
            return back()->with('error', 'Pendaftaran TKA saat ini ditutup.');
        }

        $request->validate([
            'tka_subject_id' => 'nullable|exists:tka_subjects,id',
            'tka_subject_ids' => 'nullable|array',
            'tka_subject_ids.*' => 'exists:tka_subjects,id',
        ]);

        $subjectIds = [];
        if (!empty($request->tka_subject_ids) && is_array($request->tka_subject_ids)) {
            $subjectIds = array_unique($request->tka_subject_ids);
        } elseif (!empty($request->tka_subject_id)) {
            $subjectIds = [$request->tka_subject_id];
        }

        if (empty($subjectIds)) {
            return back()->with('error', 'Silakan pilih mata pelajaran TKA yang ingin diambil.');
        }

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();

        // Validasi Kelas XII
        $classroomStudent = ClassroomStudent::with('classroom')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->first();

        if ($classroomStudent && $classroomStudent->classroom) {
            $className = strtoupper(trim($classroomStudent->classroom->name));
            if (!str_starts_with($className, 'XII') && !str_starts_with($className, '12')) {
                return back()->with('error', 'Pemilihan mata pelajaran TKA hanya diperuntukkan bagi siswa Kelas XII.');
            }
        }

        $countChosen = TkaStudent::where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->count();

        $addedCount = 0;
        foreach ($subjectIds as $subId) {
            $alreadyChosen = TkaStudent::where('student_id', $student->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('tka_subject_id', $subId)
                ->exists();

            if ($alreadyChosen) {
                continue;
            }

            if ($countChosen >= 2) {
                break;
            }

            TkaStudent::create([
                'tka_subject_id' => $subId,
                'student_id' => $student->id,
                'academic_year_id' => $activeYear->id,
                'status' => 'active',
            ]);

            $countChosen++;
            $addedCount++;
        }

        if ($addedCount === 0 && count($subjectIds) === 1) {
            return back()->with('error', 'Mata pelajaran TKA tersebut sudah dipilih atau batas kuota 2 mapel telah tercapai.');
        } elseif ($addedCount === 0) {
            return back()->with('error', 'Mata pelajaran TKA yang dipilih sudah ada dalam daftar atau batas kuota 2 mapel telah tercapai.');
        }

        return back()->with('success', 'Mata Pelajaran TKA berhasil disimpan (' . $countChosen . '/2 Mapel).');
    }

    public function selfLeave(Request $request)
    {
        if (!$request->user() || !$request->user()->hasRole('siswa')) {
            abort(403, 'Akses ditolak.');
        }

        $enabled = Setting::get('tka_registration_active', '0') === '1';
        if (!$enabled) {
            return back()->with('error', 'Pendaftaran TKA saat ini ditutup. Tidak dapat membatalkan pilihan.');
        }

        $validated = $request->validate([
            'tka_subject_id' => 'required|exists:tka_subjects,id',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();

        $existing = TkaStudent::where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('tka_subject_id', $validated['tka_subject_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Pilihan Mata Pelajaran TKA berhasil dibatalkan.');
        }

        return back()->with('error', 'Mata pelajaran TKA tersebut tidak ditemukan dalam daftar pilihan Anda.');
    }

    public function studentIndex(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('siswa')) {
            abort(403, 'Akses ditolak.');
        }

        $student = Student::where('user_id', $user->id)->first();
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$student || !$activeYear) {
            return Inertia::render('Akademik/Tka/Student/Index', [
                'subjects' => [],
                'myTka' => null,
                'isRegistrationActive' => false,
                'isClassXII' => false,
                'student' => null,
                'activeYear' => null,
            ]);
        }

        $classroomStudent = ClassroomStudent::with('classroom')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->first();

        $isClassXII = false;
        if ($classroomStudent && $classroomStudent->classroom) {
            $className = strtoupper(trim($classroomStudent->classroom->name));
            if (str_starts_with($className, 'XII') || str_starts_with($className, '12')) {
                $isClassXII = true;
            }
        }

        $myTkas = TkaStudent::with('subject')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->orderBy('id')
            ->get();

        $subjects = TkaSubject::where('is_active', true)
            ->when($activeYear, function ($query, $year) {
                return $query->where('academic_year_id', $year->id);
            })
            ->orderBy('code')
            ->get();

        $isRegistrationActive = Setting::get('tka_registration_active', '0') === '1';

        return Inertia::render('Akademik/Tka/Student/Index', [
            'subjects' => $subjects,
            'myTka' => $myTkas->first(),
            'myTkas' => $myTkas,
            'isRegistrationActive' => $isRegistrationActive,
            'isClassXII' => $isClassXII,
            'student' => $student,
            'activeYear' => $activeYear,
        ]);
    }

    public function recap(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif tidak ditemukan.');
        }

        // 1. Ambil semua kelas XII pada tahun ajaran aktif
        $classrooms = Classroom::where('academic_year_id', $activeYear->id)
            ->where(function ($q) {
                $q->where('level', 12)->orWhere('name', 'like', '%XII%');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        // 2. Tentukan kelas terpilih (default: kelas pertama atau filter 'all')
        $selectedClassroomId = $request->input('classroom_id');
        if (!$selectedClassroomId && $classrooms->isNotEmpty()) {
            $selectedClassroomId = $classrooms->first()->id;
        }

        // 3. Ambil data siswa pada kelas terpilih
        $studentsQuery = Student::whereHas('classrooms', function ($q) use ($activeYear, $selectedClassroomId, $classrooms) {
            $q->where('classroom_students.academic_year_id', $activeYear->id)
              ->where('classroom_students.status', 'aktif');

            if ($selectedClassroomId && $selectedClassroomId !== 'all') {
                $q->where('classrooms.id', $selectedClassroomId);
            } else {
                $classIds = $classrooms->pluck('id')->toArray();
                $q->whereIn('classrooms.id', $classIds);
            }
        })
        ->with(['classrooms' => function ($q) use ($activeYear) {
            $q->where('classroom_students.academic_year_id', $activeYear->id)
              ->where('classroom_students.status', 'aktif');
        }])
        ->orderBy('full_name');

        $students = $studentsQuery->get(['id', 'full_name', 'nis', 'nisn', 'gender']);

        // 4. Ambil data pilihan TKA siswa pada tahun ajaran aktif
        $tkaRecords = TkaStudent::with('tkaSubject')
            ->where('academic_year_id', $activeYear->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('student_id');

        // 5. Transform data siswa menjadi rekap
        $studentList = $students->map(function ($student) use ($tkaRecords) {
            $tkaList = $tkaRecords->get($student->id, collect());
            $activeClassroom = $student->classrooms->first();

            $tka1 = $tkaList->get(0);
            $tka2 = $tkaList->get(1);

            $subjectNames = $tkaList->map(function ($item, $index) {
                return ($index + 1) . '. ' . ($item->tkaSubject ? $item->tkaSubject->name : '-');
            })->implode(', ');

            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nis' => $student->nis,
                'full_name' => $student->full_name,
                'name' => $student->full_name,
                'gender_label' => $student->gender_label,
                'classroom_name' => $activeClassroom ? $activeClassroom->name : '-',
                'tka_subject_1_id' => $tka1 ? $tka1->tka_subject_id : null,
                'tka_subject_1_name' => ($tka1 && $tka1->tkaSubject) ? $tka1->tkaSubject->name : '-',
                'tka_subject_1_code' => ($tka1 && $tka1->tkaSubject) ? $tka1->tkaSubject->code : null,
                'tka_subject_2_id' => $tka2 ? $tka2->tka_subject_id : null,
                'tka_subject_2_name' => ($tka2 && $tka2->tkaSubject) ? $tka2->tkaSubject->name : '-',
                'tka_subject_2_code' => ($tka2 && $tka2->tkaSubject) ? $tka2->tkaSubject->code : null,
                'tka_subject_name' => $subjectNames ?: '-',
                'has_chosen' => $tkaList->count() > 0,
                'chosen_count' => $tkaList->count(),
                'is_complete' => $tkaList->count() === 2,
            ];
        })->values();

        // 6. Ringkasan per mata pelajaran
        $allTkaRecords = $tkaRecords->flatten();
        $subjects = TkaSubject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $subjectCounts = $subjects->map(function ($subject) use ($allTkaRecords) {
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'count' => $allTkaRecords->where('tka_subject_id', $subject->id)->count(),
            ];
        });

        $summary = [
            'total_students' => $studentList->count(),
            'complete_count' => $studentList->where('is_complete', true)->count(),
            'incomplete_count' => $studentList->where('chosen_count', 1)->count(),
            'not_chosen_count' => $studentList->where('chosen_count', 0)->count(),
            'chosen_count' => $studentList->where('has_chosen', true)->count(),
            'subject_counts' => $subjectCounts,
        ];

        return Inertia::render('Akademik/Tka/Recap/Index', [
            'classrooms' => $classrooms,
            'selected_classroom_id' => $selectedClassroomId,
            'students' => $studentList,
            'summary' => $summary,
            'active_year' => $activeYear,
        ]);
    }
}
