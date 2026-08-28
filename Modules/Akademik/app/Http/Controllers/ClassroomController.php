<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\ClassroomMemberTemplateExport;
use Modules\Akademik\Exports\ClassroomTemplateExport;
use Modules\Akademik\Imports\ClassroomImport;
use Modules\Akademik\Imports\ClassroomMemberImport;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Teacher;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear ? $activeYear->id : null;
        $query = Classroom::with(['teacher', 'academicYear'])
            ->when($activeYearId, function ($q) use ($activeYearId) {
                $q->where('academic_year_id', $activeYearId);
            })
            ->withCount(['students' => function ($q) use ($activeYearId) {
                // Hanya hitung siswa yang statusnya 'aktif' di kelas tersebut
                $q->where('classroom_students.status', 'aktif');
                if ($activeYearId) {
                    $q->where('classroom_students.academic_year_id', $activeYearId);
                }
            }])
            ->orderBy('level', 'asc')
            ->orderBy('name', 'asc');
        // Logic Filter Sampah
        if ($request->has('trash') && $request->trash == 'true') {
            $query->onlyTrashed();
        }

        if ($request->search) {
            $query->where('name', 'ilike', '%'.$request->search.'%');
        }

        // Ambil data untuk Dropdown di Form
        // 1. Guru (Untuk Wali Kelas)
        $teachers = Teacher::orderBy('full_name')->select('id', 'full_name', 'gelar_belakang')->get();

        // 2. Tahun Ajaran Aktif (Default)
        $activeYear = AcademicYear::where('is_active', true)->first();

        return Inertia::render('Akademik/Classroom/Index', [
            'classrooms' => $query->paginate(10),
            'teachers' => $teachers,
            'activeYear' => $activeYear,
            'filters' => $request->only(['search', 'trash']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'level' => 'required|integer|in:10,11,12',
            'major' => 'nullable|string', // IPA, IPS, dll
            'teacher_id' => 'nullable|exists:teachers,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        Classroom::create($request->all());

        return redirect()->back()->with('success', 'Kelas berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:50',
            'level' => 'required|integer|in:10,11,12',
            'major' => 'nullable|string',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $classroom->update($request->all());

        return redirect()->back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        // Cek apakah ada siswa di kelas ini? (Nanti ditambahkan validasinya)
        Classroom::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }

    public function restore($id)
    {
        $classroom = Classroom::onlyTrashed()->findOrFail($id);
        $classroom->restore();

        return redirect()->back()->with('success', 'Kelas berhasil dipulihkan.');
    }

    public function forceDelete($id)
    {
        $classroom = Classroom::onlyTrashed()->findOrFail($id);

        // TODO: Nanti kalau Modul Siswa sudah ada, buka komentar ini:
        // if ($classroom->students()->count() > 0) {
        //     return redirect()->back()->with('error', 'Gagal! Kelas ini masih memiliki siswa.');
        // }

        $classroom->forceDelete();

        return redirect()->back()->with('success', 'Data kelas dihapus permanen.');
    }

    // Download Template
    public function downloadTemplate()
    {
        return Excel::download(new ClassroomTemplateExport, 'template_kelas.xlsx');
    }

    // Proses Import
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ClassroomImport, $request->file('file'));

            return redirect()->back()->with('success', 'Data kelas berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }

    public function getAvailableStudents()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return response()->json([]);
        }

        // Cari siswa yang TIDAK ADA di tabel pivot classroom_students pada tahun ini
        // dan pastikan akun user-nya masih aktif (bukan alumni)
        $students = Student::whereDoesntHave('classrooms', function ($q) use ($activeYear) {
            $q->where('classroom_students.academic_year_id', $activeYear->id);
        })
            ->whereHas('user', function ($q) {
                $q->where('is_active', 1);
            })
            ->select('id', 'full_name', 'nis')
            ->orderBy('full_name')
            ->get();

        return response()->json($students);
    }

    // Action Simpan Anggota
    public function addMembers(Request $request, $id)
    {
        $request->validate([
            'student_ids' => 'required|array', // Array ID Siswa
            'student_ids.*' => 'exists:students,id',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return back()->with('error', 'Tahun ajaran tidak aktif.');
        }

        $classroom = Classroom::findOrFail($id);

        DB::transaction(function () use ($request, $classroom, $activeYear) {
            foreach ($request->student_ids as $studentId) {
                // Masukkan ke pivot
                ClassroomStudent::firstOrCreate([
                    'academic_year_id' => $activeYear->id,
                    'classroom_id' => $classroom->id,
                    'student_id' => $studentId,
                ], [
                    'status' => 'aktif',
                ]);
            }
        });

        return back()->with('success', count($request->student_ids).' Siswa berhasil dimasukkan ke kelas '.$classroom->name);
    }

    public function show($id)
    {
        // Ambil Data Kelas + Wali Kelas + Tahun Ajaran
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear ? $activeYear->id : null;
        $classroom = Classroom::with(['teacher', 'academicYear'])->findOrFail($id);

        // Ambil Siswa yang ada di kelas ini (via tabel pivot)
        // Kita filter yang statusnya 'aktif' saja
        $students = $classroom->students()
            ->with('religion')
            ->wherePivot('status', 'aktif')
            ->wherePivot('academic_year_id', $activeYearId)
            ->orderBy('full_name')
            ->paginate(40); // Tampilkan 40 siswa per halaman

        return Inertia::render('Akademik/Classroom/Detail', [
            'classroom' => $classroom,
            'students' => $students,
            'activeYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    // 2. Method REMOVE MEMBER (Keluarkan Siswa dari Kelas)
    public function removeMember($classroomId, $studentId)
    {
        $classroom = Classroom::findOrFail($classroomId);

        // Hapus data di tabel pivot (classroom_students)
        // Cara Hard Delete Pivot (Hilang dari kelas, tapi Siswa/User tetap aman)
        ClassroomStudent::where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->delete();

        return redirect()->back()->with('success', 'Siswa berhasil dikeluarkan dari kelas ini.');
    }

    public function downloadMemberTemplate()
    {
        return Excel::download(new ClassroomMemberTemplateExport, 'template_anggota_kelas.xlsx');
    }

    // 2. Proses Import Anggota
    public function importMembers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ClassroomMemberImport, $request->file('file'));

            return redirect()->back()->with('success', 'Pembagian kelas berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }

    public function copyFromPreviousYear(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Belum ada tahun ajaran aktif.');
        }

        // Cari tahun ajaran sebelumnya (berdasarkan start_date terkecil yang mendekati tahun aktif)
        $previousYear = AcademicYear::where('id', '!=', $activeYear->id)
            ->where('start_date', '<', $activeYear->start_date)
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$previousYear) {
            return redirect()->back()->with('error', 'Tahun ajaran sebelumnya tidak ditemukan.');
        }

        // Cek apakah tahun ajaran aktif sudah punya kelas
        $existingCount = Classroom::where('academic_year_id', $activeYear->id)->count();
        if ($existingCount > 0) {
            return redirect()->back()->with('error', 'Gagal menyalin! Sudah terdapat data kelas di tahun ajaran aktif saat ini.');
        }

        $previousClassrooms = Classroom::where('academic_year_id', $previousYear->id)->get();
        if ($previousClassrooms->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada kelas yang dapat disalin dari tahun ajaran sebelumnya.');
        }

        DB::transaction(function () use ($previousClassrooms, $activeYear) {
            foreach ($previousClassrooms as $class) {
                Classroom::create([
                    'academic_year_id' => $activeYear->id,
                    'name' => $class->name,
                    'level' => $class->level,
                    'major' => $class->major,
                    'teacher_id' => $class->teacher_id, // Salin wali kelas sebagai draft
                ]);
            }
        });

        return redirect()->back()->with('success', 'Berhasil menyalin ' . $previousClassrooms->count() . ' kelas dari tahun ajaran sebelumnya.');
    }

    public function promotionIndex()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();

        return Inertia::render('Akademik/Classroom/Promotion', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
        ]);
    }

    public function getClassroomsByYear($yearId)
    {
        // Hanya ambil kelas yang masih memiliki minimal 1 siswa dengan status 'aktif'
        // Kelas yang sudah 100% diproses (naik/tinggal/lulus) tidak akan muncul di opsi 'Kelas Asal'
        $classrooms = Classroom::where('academic_year_id', $yearId)
            ->whereHas('classroomStudents', function ($q) {
                $q->where('status', ClassroomStudent::STATUS_ACTIVE);
            })
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level', 'major']);
            
        return response()->json($classrooms);
    }

    public function getPromotionStudents(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $students = $classroom->students()
            ->wherePivot('academic_year_id', $request->academic_year_id)
            ->wherePivotIn('status', ['aktif', 'retained', 'promoted', 'lulus'])
            ->orderBy('full_name')
            ->get(['students.id', 'students.full_name', 'students.nis', 'students.nisn', 'classroom_students.status as pivot_status']);

        return response()->json($students);
    }

    public function promoteStudents(Request $request)
    {
        $request->validate([
            'source_year_id' => 'required|exists:academic_years,id',
            'source_classroom_id' => 'required|exists:classrooms,id',
            'promotions' => 'required|array',
            'promotions.*.student_id' => 'required|exists:students,id',
            'promotions.*.status' => 'required|in:naik,tinggal,lulus,keluar,batal',
        ]);

        $sourceYearId = $request->source_year_id;
        $sourceClassroomId = $request->source_classroom_id;

        DB::transaction(function () use ($request, $sourceYearId, $sourceClassroomId) {
            foreach ($request->promotions as $promo) {
                $studentId = $promo['student_id'];
                $status = $promo['status'];

                // 1. Tentukan status lama untuk histori
                $oldStatus = ClassroomStudent::STATUS_ACTIVE;
                if ($status === 'naik') $oldStatus = ClassroomStudent::STATUS_PROMOTED;
                if ($status === 'tinggal') $oldStatus = ClassroomStudent::STATUS_RETAINED;
                if ($status === 'lulus') $oldStatus = ClassroomStudent::STATUS_LULUS;
                if ($status === 'keluar') $oldStatus = ClassroomStudent::STATUS_KELUAR;
                if ($status === 'batal') $oldStatus = ClassroomStudent::STATUS_ACTIVE;

                // 2. Update status siswa di tahun ajaran asal
                ClassroomStudent::where('classroom_id', $sourceClassroomId)
                    ->where('academic_year_id', $sourceYearId)
                    ->where('student_id', $studentId)
                    ->update(['status' => $oldStatus]);

                // 3. Aktivasi / Nonaktivasi Akun Alumni
                if ($status === 'lulus') {
                    $student = Student::find($studentId);
                    if ($student && $student->user_id) {
                        \App\Models\User::where('id', $student->user_id)->update(['is_active' => false]);
                    }
                } elseif ($status === 'batal') {
                    $student = Student::find($studentId);
                    if ($student && $student->user_id) {
                        \App\Models\User::where('id', $student->user_id)->update(['is_active' => true]);
                    }
                }
            }
        });

        return redirect()->back()
            ->with('success', 'Proses kenaikan kelas/kelulusan selesai diproses.');
    }
}
