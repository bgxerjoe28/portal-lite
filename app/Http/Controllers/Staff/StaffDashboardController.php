<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\DaftarUlang\Models\NewStudent;
use Illuminate\Support\Facades\Auth;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $activeYear ? $activeYear->id : null;

        $spmbClosed = \App\Models\Setting::get('lock_daftar_ulang_login', '0') === '1';

        $stats = [
            'total' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->count() : 0,
            'imported' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'imported')->count() : 0,
            'filling' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'filling')->count() : 0,
            'uploading' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'uploading')->count() : 0,
            'revision' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'revision')->count() : 0,
            'registered' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'registered')->count() : 0,
            'verified' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'verified')->count() : 0,
            'migrated' => $selectedYearId ? NewStudent::where('academic_year_id', $selectedYearId)->where('status', 'migrated')->count() : 0,
        ];

        $studentStats = [];
        $classroomsLp = [];
        $religionsStats = [];

        if ($spmbClosed) {
            // 1. Data Siswa Summary
            $studentStats = [
                'total_siswa' => \Modules\Akademik\Models\Student::whereHas('user', function($q) {
                    $q->where('is_active', true);
                })->count(),
                'total_kelas' => $selectedYearId ? \Modules\Akademik\Models\Classroom::where('academic_year_id', $selectedYearId)->count() : 0,
            ];

            // 2. Kelas LP (Laki-laki & Perempuan count per class)
            if ($selectedYearId) {
                $classrooms = \Modules\Akademik\Models\Classroom::where('academic_year_id', $selectedYearId)->get();
                foreach ($classrooms as $c) {
                    $l = \Illuminate\Support\Facades\DB::table('classroom_students')
                        ->join('students', 'classroom_students.student_id', '=', 'students.id')
                        ->join('users', 'students.user_id', '=', 'users.id')
                        ->where('classroom_students.classroom_id', $c->id)
                        ->where('classroom_students.status', 'aktif')
                        ->where('users.is_active', true)
                        ->where('students.gender', true)
                        ->count();

                    $p = \Illuminate\Support\Facades\DB::table('classroom_students')
                        ->join('students', 'classroom_students.student_id', '=', 'students.id')
                        ->join('users', 'students.user_id', '=', 'users.id')
                        ->where('classroom_students.classroom_id', $c->id)
                        ->where('classroom_students.status', 'aktif')
                        ->where('users.is_active', true)
                        ->where('students.gender', false)
                        ->count();

                    $classroomsLp[] = [
                        'name' => $c->name,
                        'l' => $l,
                        'p' => $p,
                        'total' => $l + $p,
                    ];
                }
            }

            // 3. Statistik Agama
            $religions = \Modules\Akademik\Models\Religion::all();
            foreach ($religions as $r) {
                $count = \Modules\Akademik\Models\Student::where('religion_id', $r->id)
                    ->whereHas('user', function($q) {
                        $q->where('is_active', true);
                    })->count();
                if ($count > 0) {
                    $religionsStats[] = [
                        'name' => $r->name,
                        'total' => $count,
                    ];
                }
            }
        }

        return Inertia::render('Dashboard/DashboardStaff', [
            'stats' => $stats,
            'activeYear' => $activeYear,
            'spmbClosed' => $spmbClosed,
            'studentStats' => $studentStats,
            'classroomsLp' => $classroomsLp,
            'religionsStats' => $religionsStats,
        ]);
    }
}
