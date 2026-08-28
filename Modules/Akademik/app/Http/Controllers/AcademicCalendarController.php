<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Services\AcademicCalendarService;
use Modules\Akademik\Models\AcademicEvent;
use Carbon\Carbon;

class AcademicCalendarController extends Controller
{
    public function __construct(
        protected AcademicCalendarService $calendarService
    ) {}

    /**
     * Halaman input kalender (Admin)
     */
    public function index()
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        return Inertia::render('Akademik/Calendar/Index', [
            'academicYear' => $activeYear,
            'events'       => $this->calendarService->getEvents($activeYear),
        ]);
    }

    /**
     * Simpan event kalender (single / range)
     * Semua diperlakukan sebagai start_date - end_date
     */
    public function store(Request $request)
    {
        $year = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'title'            => 'required|string|max:255',
            'type'             => 'required|in:holiday,event,exam,info',
            'is_holiday'       => 'required|boolean',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date',
        ]);

        $this->calendarService->validateRangeInSemester(
            $year,
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date'])
        );

        $this->calendarService->storeEvent($data);

        return back()->with('success', 'Event kalender berhasil disimpan');
    }

    /**
     * Hapus event kalender
     */
    public function destroy($id)
    {
        $this->calendarService->deleteEvent($id);

        return back()->with('success', 'Event berhasil dihapus');
    }

    /**
     * View kalender + hari efektif (Read-only)
     */
    public function view()
    {
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $events = AcademicEvent::where('academic_year_id', $academicYear->id)
            ->orderBy('start_date')
            ->get();

        $effectiveDays = $this->calendarService
            ->calculateEffectiveDays($academicYear);

        return Inertia::render('Akademik/Calendar/View', [
            'academicYear'  => $academicYear,
            'events'        => $events,
            'effectiveDays' => $effectiveDays,
        ]);
    }

    /**
     * Halaman cetak kalender (Semester / Bulan)
     */
    public function print(Request $request)
    {
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();
        
        $events = AcademicEvent::where('academic_year_id', $academicYear->id)
            ->orderBy('start_date')
            ->get();
            
        return Inertia::render('Akademik/Calendar/Print', [
            'academicYear' => $academicYear,
            'events'       => $events,
            'type'         => $request->query('type', 'semester'),
            'month'        => (int) $request->query('month', date('n')),
        ]);
    }

    /**
     * API ringkas (opsional, future mobile / dashboard)
     */
    public function summary(AcademicYear $academicYear)
    {
        return response()->json([
            'effective_days' =>
                $this->calendarService->calculateEffectiveDays($academicYear),
        ]);
    }
}
