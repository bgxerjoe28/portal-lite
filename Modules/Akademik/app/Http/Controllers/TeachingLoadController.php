<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Services\AcademicCalendarService;
use Modules\Akademik\Services\TeachingLoadService;
use Inertia\Inertia;

class TeachingLoadController extends Controller
{

    public function show(
        Teacher $teacher,
        AcademicCalendarService $calendarService,
        TeachingLoadService $loadService
    ) {
        $year = AcademicYear::where('is_active', true)->firstOrFail();

        $comparison = $loadService->compareTargetVsRealization(
            $year,
            $teacher->id,
            $teacher->target_jp ?? 24,
            $calendarService
        );

        return Inertia::render('Akademik/Teacher/Dashboard', [
            'teacher' => $teacher,
            'comparison' => $comparison,
        ]);
    }
    
}
