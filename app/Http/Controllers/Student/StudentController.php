<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Akademik\Models\Schedule;
class StudentController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load(['student.classrooms', 'student.religion']);
        $student = $user->student;
        $today = now();

        // Cross-reference with new_students table if student profile has missing fields
        if ($student && class_exists('Modules\DaftarUlang\Models\NewStudent')) {
            $newStudentQuery = \Modules\DaftarUlang\Models\NewStudent::query();
            if ($student->nisn) {
                $newStudentQuery->where('nisn', $student->nisn);
            } elseif ($user->email) {
                $newStudentQuery->where('email', $user->email);
            }
            $newStudent = $newStudentQuery->first();

            if ($newStudent) {
                $columns = [
                    'nik', 'no_kk', 'akta_no', 'citizenship', 'special_needs',
                    'rt', 'rw', 'dusun', 'kelurahan', 'kecamatan', 'postal_code',
                    'latitude', 'longitude', 'residence_type', 'transportation', 'child_order',
                    'father_name', 'father_deceased', 'father_nik', 'father_birth_year',
                    'father_education', 'father_job', 'father_income', 'father_special_needs', 'father_phone',
                    'mother_name', 'mother_deceased', 'mother_nik', 'mother_birth_year',
                    'mother_education', 'mother_job', 'mother_income', 'mother_special_needs', 'mother_phone',
                    'guardian_name', 'guardian_nik', 'guardian_birth_year', 'guardian_education',
                    'guardian_job', 'guardian_income', 'guardian_phone',
                    'height', 'weight', 'head_circumference', 'distance_to_school_km',
                    'travel_time_minutes', 'sibling_count', 'periodik_phone',
                    'prev_school_type', 'prev_school_status', 'prev_school_name',
                    'file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other'
                ];

                foreach ($columns as $column) {
                    if (empty($student->$column)) {
                        $student->$column = $newStudent->$column;
                    }
                }

                if (empty($student->religion_id) && !empty($newStudent->religion)) {
                    $religionDb = \Modules\Akademik\Models\Religion::where('name', 'ilike', '%' . $newStudent->religion . '%')->first();
                    if ($religionDb) {
                        $student->religion_id = $religionDb->id;
                    }
                }
            }
        }

        // 1. Ambil Kelas Aktif Siswa (Sesuai Tahun Ajaran Aktif)
        $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();
        $classroom = $student ? ($student->classrooms()->where('classrooms.academic_year_id', $activeYear?->id)->first() ?? $student->classrooms->last()) : null;

        // 2. Map Hari Hari Ini (Format Indonesia & English untuk pencocokan DB)
        $mapHari = [
            'monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu',
            'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu',
        ];
        $dayEnglish = strtolower($today->format('l'));
        $dayIndo = $mapHari[$dayEnglish] ?? $dayEnglish;
        $dayTranslated = strtolower($today->translatedFormat('l'));
        $daysToMatch = array_unique([$dayIndo, $dayEnglish, $dayTranslated]);

        $schedules = [];
        if ($classroom) {
            // Query: Ambil Schedule yang punya Detail Hari Ini
            $schedules = Schedule::with(['subject', 'teacher', 'details' => function ($q) use ($daysToMatch) {
                $q->whereIn('day', $daysToMatch); // Hanya ambil detail hari ini
            }])
                ->where('classroom_id', $classroom->id)
                ->whereHas('details', function ($q) use ($daysToMatch) {
                    $q->whereIn('day', $daysToMatch); // Filter Schedule yang punya jadwal hari ini
                })
                ->get();

            // Ambil tugas terposting untuk kelas siswa
            $activeAssignments = \Modules\Penugasan\Models\Assignment::where('classroom_id', $classroom->id)
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->get();

            $submissionsMap = [];
            if ($student && $activeAssignments->count() > 0) {
                $submissionsMap = \Modules\Penugasan\Models\AssignmentSubmission::whereIn('assignment_id', $activeAssignments->pluck('id'))
                    ->where('student_id', $student->id)
                    ->get()
                    ->keyBy('assignment_id');
            }

            foreach ($schedules as $sched) {
                $assignment = $activeAssignments->firstWhere('subject_id', $sched->subject_id);
                if ($assignment) {
                    $sub = $submissionsMap->get($assignment->id);
                    $sched->active_assignment = [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'due_at' => $assignment->due_at ? $assignment->due_at->format('d M H:i') : null,
                        'has_submitted' => $sub !== null,
                        'is_grades_published' => (bool) $assignment->is_grades_published,
                        'score' => $assignment->is_grades_published ? $sub?->total_score : null,
                    ];
                } else {
                    $sched->active_assignment = null;
                }
            }
        }

        // Hitung Jumlah Tugas yang Belum Dikerjakan Siswa
        $pendingAssignmentsCount = 0;
        if ($classroom && $student) {
            $publishedAssignments = \Modules\Penugasan\Models\Assignment::where('classroom_id', $classroom->id)
                ->where('is_published', true)
                ->get();
            
            $submittedIds = \Modules\Penugasan\Models\AssignmentSubmission::whereIn('assignment_id', $publishedAssignments->pluck('id'))
                ->where('student_id', $student->id)
                ->where('is_editable', false)
                ->pluck('assignment_id')
                ->toArray();
            
            $pendingAssignmentsCount = $publishedAssignments->whereNotIn('id', $submittedIds)->count();
        }

        return Inertia::render('Dashboard/DashboardStudent', [
            'attendance_status' => null,
            'todayAttendance' => null,
            'schedules' => $schedules,
            'pendingAssignmentsCount' => $pendingAssignmentsCount,
            'stats' => [
                'H' => 0,
                'T' => 0,
                'A' => 0,
                'S' => 0,
                'I' => 0,
            ],
            'serverDate' => $today->translatedFormat('l, d F Y'),
            'student' => $student,
            'religions' => \Modules\Akademik\Models\Religion::all(),
            'school_latitude' => \App\Models\Setting::get('school_latitude', '-7.024644700237852'),
            'school_longitude' => \App\Models\Setting::get('school_longitude', '110.30857222330609'),
        ]);
    }

    public function updateBiodata(\Illuminate\Http\Request $request)
    {
        $user = Auth::user()->load('student');
        $student = $user->student;

        if (!$student) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // Validasi seluruh input data siswa (relax rules for disabled/protected fields)
        $request->validate([
            'full_name' => 'nullable|string|max:255',
            'gender' => 'nullable|boolean',
            'nisn' => 'nullable|string|regex:/^[0-9]{1,10}$/',
            'prev_school_type' => 'nullable|string|in:SMP,MTS',
            'prev_school_status' => 'nullable|string|in:Negeri,Swasta',
            'prev_school_name' => 'nullable|string|max:255',
            'nik' => 'nullable|regex:/^[0-9]{1,16}$/',
            'no_kk' => 'nullable|regex:/^[0-9]{1,16}$/',
            'birth_place' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
            'birth_date' => 'required|date',
            'akta_no' => 'nullable|string|max:255',
            'religion_id' => 'required|exists:religions,id',
            'citizenship' => 'required|string|in:WNI,WNA',
            'special_needs' => 'nullable|string|max:255',
            'address' => 'required|regex:/^[a-zA-Z0-9\s\.\-]+$/|max:1000',
            'rt' => 'required|regex:/^[0-9]+$/|max:10',
            'rw' => 'required|regex:/^[0-9]+$/|max:10',
            'kelurahan' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
            'kecamatan' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
            'postal_code' => 'required|regex:/^[0-9]+$/|max:10',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'residence_type' => 'nullable|string|max:100',
            'transportation' => 'nullable|string|max:100',
            'child_order' => 'nullable|integer|min:1',
            'phone' => 'required|regex:/^[0-9]+$/|max:25',

            // Ayah
            'father_name' => 'required|string|max:255',
            'father_deceased' => 'required|boolean',
            'father_nik' => 'nullable|string|max:16',
            'father_birth_year' => 'required_if:father_deceased,false|nullable|integer|min:1900|max:' . date('Y'),
            'father_education' => 'required_if:father_deceased,false|nullable|string|max:255',
            'father_job' => 'required_if:father_deceased,false|nullable|string|max:255',
            'father_income' => 'required_if:father_deceased,false|nullable|string|max:255',
            'father_special_needs' => 'nullable|string|max:255',
            'father_phone' => 'required_if:father_deceased,false|nullable|regex:/^[0-9]+$/|max:25',

            // Ibu
            'mother_name' => 'required|string|max:255',
            'mother_deceased' => 'required|boolean',
            'mother_nik' => 'nullable|string|max:16',
            'mother_birth_year' => 'required_if:mother_deceased,false|nullable|integer|min:1900|max:' . date('Y'),
            'mother_education' => 'required_if:mother_deceased,false|nullable|string|max:255',
            'mother_job' => 'required_if:mother_deceased,false|nullable|string|max:255',
            'mother_income' => 'required_if:mother_deceased,false|nullable|string|max:255',
            'mother_special_needs' => 'nullable|string|max:255',
            'mother_phone' => 'required_if:mother_deceased,false|nullable|regex:/^[0-9]+$/|max:25',

            // Wali
            'guardian_name' => 'nullable|string|max:255',
            'guardian_nik' => 'nullable|string|max:16',
            'guardian_birth_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'guardian_education' => 'nullable|string|max:255',
            'guardian_job' => 'nullable|string|max:255',
            'guardian_income' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|regex:/^[0-9]+$/|max:25',

            // Periodik
            'height' => 'nullable|integer|min:50|max:250',
            'weight' => 'nullable|integer|min:10|max:200',
            'head_circumference' => 'nullable|integer|min:20|max:100',
            'distance_to_school_km' => 'nullable|numeric|min:0',
            'travel_time_minutes' => 'nullable|integer|min:0',
            'sibling_count' => 'nullable|integer|min:0',
            'periodik_phone' => 'nullable|email|max:255',

            // Files
            'file_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_akta' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_ijazah' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_foto' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_other' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('students');
        $data = $request->only($columns);
        
        $protectedFields = [
            'id', 'user_id', 'file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other', 'created_at', 'updated_at', 'deleted_at',
            'full_name', 'gender', 'nisn', 'nik', 'no_kk', 'father_nik', 'mother_nik'
        ];

        if (!empty($student->guardian_name)) {
            $protectedFields = array_merge($protectedFields, [
                'guardian_name', 'guardian_nik', 'guardian_birth_year', 'guardian_education', 'guardian_job', 'guardian_income', 'guardian_phone'
            ]);
        }

        $data = array_diff_key($data, array_flip($protectedFields));

        // Uppercase / formatting transforms
        if (isset($data['full_name'])) {
            $data['full_name'] = strtoupper(trim($data['full_name']));
        }
        if (isset($data['birth_place'])) {
            $data['birth_place'] = strtoupper(trim($data['birth_place']));
        }
        if (isset($data['prev_school_name'])) {
            $data['prev_school_name'] = strtoupper(trim($data['prev_school_name']));
        }
        if (isset($data['kelurahan'])) {
            $data['kelurahan'] = ucwords(strtolower(trim($data['kelurahan'])));
        }
        if (isset($data['kecamatan'])) {
            $data['kecamatan'] = ucwords(strtolower(trim($data['kecamatan'])));
        }

        // File uploads
        $disk = config('filesystems.default');
        $fileFields = ['file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($student->$field) {
                    \Illuminate\Support\Facades\Storage::disk($disk)->delete($student->$field);
                }
                $path = $request->file($field)->store('students/files', $disk);
                $data[$field] = $path;
            }
        }

        $student->update($data);

        return redirect()->back()->with('success', 'Biodata berhasil diperbarui.');
    }

}
