<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\StudentTemplateExport;
use Modules\Akademik\Exports\StudentUpdateTemplateExport;
use Modules\Akademik\Imports\StudentImport;
use Modules\Akademik\Imports\StudentUpdateImport;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Religion;
use Modules\Akademik\Models\Student;

class StudentController extends Controller
{


    public function index(Request $request)
    {
        // 1. Ambil Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear ? $activeYear->id : null;

        $statusFilter = $request->status ?? 'aktif';

        // 2. Query Dasar (Builder - Jangan panggil through di sini!)
        $query = Student::with(['user', 'religion'])
            ->with(['classrooms' => function ($q) use ($activeYearId, $statusFilter) {
                if ($statusFilter === 'lulus') {
                    $q->where('classroom_students.status', 'lulus');
                } else {
                    $q->where('classroom_students.academic_year_id', $activeYearId)
                        ->where('classroom_students.status', 'aktif');
                }
            }])
            ->orderBy('full_name', 'asc');

        // Filter Status Alumni vs Aktif
        if ($statusFilter === 'lulus') {
            $query->whereHas('classrooms', function ($q) {
                $q->where('classroom_students.status', 'lulus');
            });
        } else {
            $query->whereDoesntHave('classrooms', function ($q) {
                $q->where('classroom_students.status', 'lulus');
            });
        }

        // 3. Filter Trash
        if ($request->trash == 'true') {
            $query->onlyTrashed();
        }

        // 4. Filter Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('nisn', 'ILIKE', "%{$search}%")
                    ->orWhere('nis', 'ILIKE', "%{$search}%");
            });
        }

        // 5. Filter Kelas
        if ($request->classroom_id && $activeYearId) {
            $classroomIds = is_array($request->classroom_id) 
                ? $request->classroom_id 
                : explode(',', $request->classroom_id);

            $query->whereHas('classrooms', function ($q) use ($classroomIds, $activeYearId, $statusFilter) {
                $q->whereIn('classrooms.id', $classroomIds);
                if ($statusFilter === 'lulus') {
                    $q->where('classroom_students.status', 'lulus');
                } else {
                    $q->where('classroom_students.academic_year_id', $activeYearId);
                }
            });
        }

        // 6. Filter Agama
        if ($request->religion_id) {
            $query->where('religion_id', $request->religion_id);
        }

        // Validasi per_page (keamanan agar tidak jebol database)
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 50, 100])) {
            $perPage = 10;
        }

        // 7. EKSEKUSI
        $students = $query->paginate($perPage)
            ->withQueryString();

        $classroomsQuery = Classroom::orderBy('level')->orderBy('name');
        if ($activeYearId) {
            $classroomsQuery->where('academic_year_id', $activeYearId);
        }
        $classrooms = $classroomsQuery->get();

        return Inertia::render('Akademik/StudentIndex', [
            'students' => $students,
            'religions' => Religion::all(),
            'classrooms' => $classrooms,
            'activeYear' => $activeYear,
            'filters' => $request->only(['search', 'trash', 'classroom_id', 'religion_id', 'status', 'per_page']),
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'gender' => 'required|boolean',
            'religion_id' => 'required|integer|exists:religions,id',
            'nis' => 'required|unique:students,nis',
            'nisn' => 'required|unique:students,nisn',
            'phone' => 'nullable|string',
            'birth_place' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
        ]);

        // Cek Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return redirect()->back()->with('error', 'Belum ada Tahun Ajaran Aktif!');
        }

        DB::transaction(function () use ($request) {
            // 1. User
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make('siswa123'),
            ]);
            $user->assignRole('siswa');

            // 2. Student (Tanpa classroom_id)
            $student = Student::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
                'religion_id' => $request->religion_id,
                'gender' => $request->gender,
                'nis' => $request->nis,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                
                'nik' => $request->nik,
                'no_kk' => $request->no_kk,
                'akta_no' => $request->akta_no,
                'citizenship' => $request->citizenship ?? 'WNI',
                'special_needs' => $request->special_needs,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'dusun' => $request->dusun,
                'kelurahan' => $request->kelurahan,
                'kecamatan' => $request->kecamatan,
                'postal_code' => $request->postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'residence_type' => $request->residence_type,
                'transportation' => $request->transportation,
                'child_order' => $request->child_order,
                
                'father_name' => $request->father_name,
                'father_deceased' => $request->father_deceased ?? false,
                'father_nik' => $request->father_nik,
                'father_birth_year' => $request->father_birth_year,
                'father_education' => $request->father_education,
                'father_job' => $request->father_job,
                'father_income' => $request->father_income,
                'father_special_needs' => $request->father_special_needs,
                'father_phone' => $request->father_phone,
                
                'mother_name' => $request->mother_name,
                'mother_deceased' => $request->mother_deceased ?? false,
                'mother_nik' => $request->mother_nik,
                'mother_birth_year' => $request->mother_birth_year,
                'mother_education' => $request->mother_education,
                'mother_job' => $request->mother_job,
                'mother_income' => $request->mother_income,
                'mother_special_needs' => $request->mother_special_needs,
                'mother_phone' => $request->mother_phone,
                
                'guardian_name' => $request->guardian_name,
                'guardian_nik' => $request->guardian_nik,
                'guardian_birth_year' => $request->guardian_birth_year,
                'guardian_education' => $request->guardian_education,
                'guardian_job' => $request->guardian_job,
                'guardian_income' => $request->guardian_income,
                'guardian_phone' => $request->guardian_phone,
                
                'height' => $request->height,
                'weight' => $request->weight,
                'head_circumference' => $request->head_circumference,
                'distance_to_school_km' => $request->distance_to_school_km,
                'travel_time_minutes' => $request->travel_time_minutes,
                'sibling_count' => $request->sibling_count,
                
                'prev_school_type' => $request->prev_school_type,
                'prev_school_status' => $request->prev_school_status,
                'prev_school_name' => $request->prev_school_name,
            ]);

        });

        return redirect()->back()->with('success', 'Siswa berhasil ditambahkan ke Tahun Ajaran '.$activeYear->name);
    }

    public function update(Request $request, $id)
    {
        $student = Student::with('user')->findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$student->user_id,
            'gender' => 'required|boolean',
            'religion_id' => 'required|integer|exists:religions,id',
            'nis' => 'required|unique:students,nis,'.$id,
            'nisn' => 'nullable|unique:students,nisn,'.$id,
            'phone' => 'nullable|string',
            'birth_place' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
        ]);

        // Ambil Tahun Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();

        DB::transaction(function () use ($request, $student, $activeYear) {
            $student->user->update(['name' => $request->full_name, 'email' => $request->email]);

            $student->update([
                'full_name' => $request->full_name,
                'religion_id' => $request->religion_id,
                'gender' => $request->gender,
                'nis' => $request->nis,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                
                'nik' => $request->nik,
                'no_kk' => $request->no_kk,
                'akta_no' => $request->akta_no,
                'citizenship' => $request->citizenship ?? 'WNI',
                'special_needs' => $request->special_needs,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'dusun' => $request->dusun,
                'kelurahan' => $request->kelurahan,
                'kecamatan' => $request->kecamatan,
                'postal_code' => $request->postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'residence_type' => $request->residence_type,
                'transportation' => $request->transportation,
                'child_order' => $request->child_order,
                
                'father_name' => $request->father_name,
                'father_deceased' => $request->father_deceased ?? false,
                'father_nik' => $request->father_nik,
                'father_birth_year' => $request->father_birth_year,
                'father_education' => $request->father_education,
                'father_job' => $request->father_job,
                'father_income' => $request->father_income,
                'father_special_needs' => $request->father_special_needs,
                'father_phone' => $request->father_phone,
                
                'mother_name' => $request->mother_name,
                'mother_deceased' => $request->mother_deceased ?? false,
                'mother_nik' => $request->mother_nik,
                'mother_birth_year' => $request->mother_birth_year,
                'mother_education' => $request->mother_education,
                'mother_job' => $request->mother_job,
                'mother_income' => $request->mother_income,
                'mother_special_needs' => $request->mother_special_needs,
                'mother_phone' => $request->mother_phone,
                
                'guardian_name' => $request->guardian_name,
                'guardian_nik' => $request->guardian_nik,
                'guardian_birth_year' => $request->guardian_birth_year,
                'guardian_education' => $request->guardian_education,
                'guardian_job' => $request->guardian_job,
                'guardian_income' => $request->guardian_income,
                'guardian_phone' => $request->guardian_phone,
                
                'height' => $request->height,
                'weight' => $request->weight,
                'head_circumference' => $request->head_circumference,
                'distance_to_school_km' => $request->distance_to_school_km,
                'travel_time_minutes' => $request->travel_time_minutes,
                'sibling_count' => $request->sibling_count,
                
                'prev_school_type' => $request->prev_school_type,
                'prev_school_status' => $request->prev_school_status,
                'prev_school_name' => $request->prev_school_name,
            ]);

            // UPDATE KELAS (PIVOT)
            // Cek apakah siswa sudah punya kelas di tahun ini?
            if ($activeYear && $request->classroom_id) {
                ClassroomStudent::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $activeYear->id,
                    ],
                    [
                        'classroom_id' => $request->classroom_id, // Pindah Kelas
                        'status' => 'aktif',
                    ]
                );
            }
        });

        return redirect()->back()->with('success', 'Data siswa diperbarui.');
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->user()->delete(); // Soft Delete User

        $student->delete(); // Soft Delete Student

        return redirect()->back()->with('success', 'Siswa dinonaktifkan.');
    }

    public function restore($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        if ($student->user()->withTrashed()->first()) {
            $student->user()->withTrashed()->restore();
        }
        $student->restore();

        return redirect()->back()->with('success', 'Siswa dipulihkan.');
    }

    public function forceDelete($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        if ($student->user()->withTrashed()->first()) {
            $student->user()->withTrashed()->forceDelete();
        }
        $student->forceDelete();

        return redirect()->back()->with('success', 'Siswa dihapus permanen.');
    }

    public function downloadTemplate()
    {
        return Excel::download(new StudentTemplateExport, 'template_siswa.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new StudentImport, $request->file('file'));

            return redirect()->back()->with('success', 'Data siswa berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }

    public function downloadTemplateUpdate()
    {
        return Excel::download(new StudentUpdateTemplateExport, 'template_update_siswa.xlsx');
    }

    public function importDataKosong(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new StudentUpdateImport, $request->file('file'));

            return back()->with('success', 'Data tambahan siswa berhasil di-import dan diamankan!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }
    public function generateNis(Request $request)
    {
        $request->validate(['start_nis' => 'nullable|integer']);
        $startNis = $request->start_nis;

        // Cari NIS terakhir yang bukan null, termasuk yang sudah dihapus/keluar
        $allNis = Student::withTrashed()->whereNotNull('nis')->pluck('nis')->map(function($n) { return (int) preg_replace('/[^0-9]/', '', $n); });
        
        if ($startNis) {
            $currentNis = ((int) $startNis) - 1;
        } else {
            $currentNis = $allNis->isEmpty() ? 0 : $allNis->max();
        }

        // Cari siswa yang NIS nya masih kosong (null atau string kosong), urutkan berdasar nama
        $studentsWithoutNis = Student::whereNull('nis')
            ->orWhere('nis', '')
            ->orderBy('full_name', 'asc')
            ->get();

        if ($studentsWithoutNis->isEmpty()) {
            return redirect()->back()->with('info', 'Semua siswa sudah memiliki NIS.');
        }

        $generatedCount = 0;
        
        DB::beginTransaction();
        try {
            foreach ($studentsWithoutNis as $student) {
                $currentNis++;
                $student->nis = (string) $currentNis;
                $student->save();
                $generatedCount++;
            }
            DB::commit();

            return redirect()->back()->with('success', "Berhasil me-generate NIS baru untuk {$generatedCount} siswa secara berurutan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal me-generate NIS: ' . $e->getMessage());
        }
    }

    public function bulkUpdateNis(Request $request)
    {
        $request->validate([
            'students' => 'required|array',
            'students.*.id' => 'required|exists:students,id',
            'students.*.nis' => 'required|string',
            'students.*.nisn' => 'nullable|string',
        ]);

        $studentsData = $request->students;
        $updatedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($studentsData as $data) {
                // Check if nis is unique (excluding current student)
                $nisExists = Student::where('nis', $data['nis'])
                                    ->where('id', '!=', $data['id'])
                                    ->exists();
                if ($nisExists) {
                    $studentRecord = Student::find($data['id']);
                    $errors[] = "NIS {$data['nis']} sudah digunakan siswa lain (Bentrok pada siswa: {$studentRecord->full_name}).";
                    continue;
                }

                // Check if nisn is unique (excluding current student) if provided
                if (!empty($data['nisn'])) {
                    $nisnExists = Student::where('nisn', $data['nisn'])
                                        ->where('id', '!=', $data['id'])
                                        ->exists();
                    if ($nisnExists) {
                        $studentRecord = Student::find($data['id']);
                        $errors[] = "NISN {$data['nisn']} sudah digunakan siswa lain (Bentrok pada siswa: {$studentRecord->full_name}).";
                        continue;
                    }
                }

                $student = Student::find($data['id']);
                $student->nis = $data['nis'];
                $student->nisn = $data['nisn'] ?? null;
                $student->save();
                $updatedCount++;
            }

            if (count($errors) > 0) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Gagal update massal karena ada data yang bentrok: ' . implode(" ", $errors));
            }

            DB::commit();
            return redirect()->back()->with('success', "Berhasil mengupdate NIS secara massal untuk {$updatedCount} siswa.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat bulk update: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        // 1. Ambil Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear ? $activeYear->id : null;

        $statusFilter = $request->status ?? 'aktif';

        $query = Student::with(['user', 'religion'])
            ->with(['classrooms' => function ($q) use ($activeYearId, $statusFilter) {
                if ($statusFilter === 'lulus') {
                    $q->where('classroom_students.status', 'lulus');
                } else {
                    $q->where('classroom_students.academic_year_id', $activeYearId)
                        ->where('classroom_students.status', 'aktif');
                }
            }])
            ->orderBy('full_name', 'asc');

        // Filter Status Alumni vs Aktif
        if ($statusFilter === 'lulus') {
            $query->whereHas('classrooms', function ($q) {
                $q->where('classroom_students.status', 'lulus');
            });
        } else {
            $query->whereDoesntHave('classrooms', function ($q) {
                $q->where('classroom_students.status', 'lulus');
            });
        }

        // Filter Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('nisn', 'ILIKE', "%{$search}%")
                    ->orWhere('nis', 'ILIKE', "%{$search}%");
            });
        }

        // Filter Kelas
        if ($request->classroom_id && $activeYearId) {
            $classroomIds = is_array($request->classroom_id) 
                ? $request->classroom_id 
                : explode(',', $request->classroom_id);

            $query->whereHas('classrooms', function ($q) use ($classroomIds, $activeYearId, $statusFilter) {
                $q->whereIn('classrooms.id', $classroomIds);
                if ($statusFilter === 'lulus') {
                    $q->where('classroom_students.status', 'lulus');
                } else {
                    $q->where('classroom_students.academic_year_id', $activeYearId);
                }
            });
        }

        // Filter Agama
        if ($request->religion_id) {
            $query->where('religion_id', $request->religion_id);
        }

        $students = $query->get();

        return Excel::download(new \Modules\Akademik\Exports\StudentsExport($students), 'data_siswa.xlsx');
    }

    public function exportBulkNis(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear ? $activeYear->id : null;
        $statusFilter = $request->status ?? 'aktif';

        $query = Student::with(['classrooms' => function ($q) use ($activeYearId, $statusFilter) {
                if ($statusFilter === 'lulus') {
                    $q->where('classroom_students.status', 'lulus');
                } else {
                    $q->where('classroom_students.academic_year_id', $activeYearId)
                        ->where('classroom_students.status', 'aktif');
                }
            }])
            ->orderBy('full_name', 'asc');

        if ($statusFilter === 'lulus') {
            $query->whereHas('classrooms', function ($q) {
                $q->where('classroom_students.status', 'lulus');
            });
        } else {
            $query->whereDoesntHave('classrooms', function ($q) {
                $q->where('classroom_students.status', 'lulus');
            });
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('nisn', 'ILIKE', "%{$search}%")
                    ->orWhere('nis', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->classroom_id && $activeYearId) {
            $classroomIds = is_array($request->classroom_id) 
                ? $request->classroom_id 
                : explode(',', $request->classroom_id);

            $query->whereHas('classrooms', function ($q) use ($classroomIds, $activeYearId, $statusFilter) {
                $q->whereIn('classrooms.id', $classroomIds);
                if ($statusFilter === 'lulus') {
                    $q->where('classroom_students.status', 'lulus');
                } else {
                    $q->where('classroom_students.academic_year_id', $activeYearId);
                }
            });
        }

        $students = $query->get();

        return Excel::download(new \Modules\Akademik\Exports\StudentBulkNisExport($students), 'bulk_edit_nis_siswa.xlsx');
    }

    public function importBulkNis(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            $import = new \Modules\Akademik\Imports\StudentBulkNisImport();
            Excel::import($import, $request->file('file'));
            
            if (count($import->errors) > 0) {
                return back()->with('error', 'Gagal import sebagian data karena bentrok: ' . implode(" ", $import->errors));
            }
            return back()->with('success', "Berhasil mengupdate NIS secara massal untuk {$import->updatedCount} siswa.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import (Sistem): '.$e->getMessage() . ' di baris ' . $e->getLine());
        }
    }
}
