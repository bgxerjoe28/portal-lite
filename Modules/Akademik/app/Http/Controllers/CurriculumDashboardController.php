<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Services\AcademicCalendarService;
use Modules\Akademik\Services\TeachingLoadService;
use Inertia\Inertia;

class CurriculumDashboardController extends Controller
{
    public function index(
        AcademicCalendarService $calendarService,
        TeachingLoadService $teachingLoadService,
        \Illuminate\Http\Request $request
    ) {
        // 1️⃣ Tahun ajaran aktif
        $year = AcademicYear::where('is_active', true)->firstOrFail();

        // 2️⃣ Hitung minggu efektif (berbasis hari sekolah & libur)
        // contoh hasil: 20.5 minggu
        $effectiveWeeks = $calendarService->calculateEffectiveWeeks($year);

        // Filter untuk guru spesifik jika diakses dari rute teacher atau role guru
        $teacherId = null;
        if ($request->is('teacher/*') || (auth()->check() && auth()->user()->hasRole('guru'))) {
            $teacher = \Modules\Akademik\Models\Teacher::where('user_id', auth()->id())->first();
            if ($teacher) {
                $teacherId = $teacher->id;
            }
        }

        // 3️⃣ Ambil dashboard kurikulum (summary + rows + details)
        $dashboard = $teachingLoadService->curriculumDashboard(
            $year,
            $effectiveWeeks,
            $calendarService,
            $teacherId // Filter specific teacher if applicable
        );

        return Inertia::render('Akademik/DashboardKurikulum/Index', [
            'year'    => $year,
            'summary' => $dashboard['summary'],
            'rows'    => $dashboard['rows'],
        ]);
    }
}