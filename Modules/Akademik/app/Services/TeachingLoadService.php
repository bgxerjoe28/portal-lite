<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\Schedule;
use Illuminate\Support\Collection;

class TeachingLoadService
{
    /**
     * Weekly JP REAL dari jadwal (sum slot dari schedule_details)
     */
    public function calculateWeeklyJpByTeacher(AcademicYear $year, int $teacherId): int
    {
        return Schedule::query()
            ->where('academic_year_id', $year->id)
            ->where('teacher_id', $teacherId)
            ->with('details')
            ->get()
            ->flatMap(fn ($s) => $s->details)
            ->sum(fn ($d) => max(0, (int)$d->end_slot - (int)$d->start_slot + 1));
    }

    /**
     * Build detail per assignment (mapel-kelas) untuk drilldown
     * - weekly_jp: sum slot per minggu untuk assignment tsb
     * - by_day: breakdown per hari (mon=>2, wed=>3, ...)
     * - occurrences: berapa kali hari itu muncul di semester (potong libur + school_days)
     * - semester_jp: Σ(by_day[day] * occurrences[day])
     */
    protected function buildTeacherDetails(
        AcademicYear $year,
        Teacher $teacher,
        AcademicCalendarService $calendarService
    ): array {
        $detailsRows = [];

        $assignments = Schedule::query()
            ->where('academic_year_id', $year->id)
            ->where('teacher_id', $teacher->id)
            ->with(['subject', 'classroom', 'details'])
            ->get()
            ->groupBy(fn ($s) => $s->subject_id . '-' . $s->classroom_id);

        foreach ($assignments as $group) {
            $first = $group->first();

            // kumpulkan semua detail untuk assignment ini
            $detailCol = $group->flatMap(fn ($s) => $s->details);

            // hitung JP per hari (mon/tue/...) dalam 1 minggu
            $jpByDay = $detailCol
                ->map(function ($d) use ($calendarService) {
                    $dayKey = $calendarService->normalizeDay($d->day); // senin -> mon
                    $jp     = max(0, (int)$d->end_slot - (int)$d->start_slot + 1);
                    return ['day' => $dayKey, 'jp' => $jp];
                })
                ->filter(fn ($x) => !empty($x['day']))
                ->groupBy('day')
                ->map(fn ($items) => $items->sum('jp')); // ['mon'=>2,'wed'=>3]

            $weeklyJp = (int) $jpByDay->sum();

            // occurrences per day dalam semester
            $occ = $calendarService->countEffectiveOccurrencesByDayKeys(
                $year,
                $jpByDay->keys()
            ); // ['mon'=>20,'wed'=>21]

            // semester_jp = Σ (jpByDay[day] * occ[day])
            $semesterJp = 0;
            foreach ($jpByDay as $day => $jpPerDay) {
                $semesterJp += (int)$jpPerDay * (int)($occ[$day] ?? 0);
            }

            $detailsRows[] = [
                'subject'      => $first->subject->name ?? '-',
                'classroom'    => $first->classroom->name ?? '-',
                'weekly_jp'    => $weeklyJp,
                'hep_days'     => $occ,  
                'jp_by_day'    => $jpByDay->toArray(),     // buat UI drilldown
                'occurrences'  => $occ,                    // buat UI drilldown
                'semester_jp'  => $semesterJp,
            ];
        }

        return $detailsRows;
    }

    /**
     * Compare Target vs Realisasi (weekly & semester)
     */
    public function compareTargetVsRealization(
        AcademicYear $year,
        Teacher $teacher,
        float $effectiveWeeks,
        AcademicCalendarService $calendarService
    ): array {
        // target mingguan: nanti bisa ambil dari kolom teacher (kalau sudah ada), sekarang default
        $targetWeekly = 24;

        $details = $this->buildTeacherDetails($year, $teacher, $calendarService);

        // realized weekly = sum weekly_jp dari semua assignment
        $realizedWeekly = (int) collect($details)->sum('weekly_jp');

        // realized semester = sum semester_jp dari semua assignment
        $realizedSemester = (int) collect($details)->sum('semester_jp');

        // target semester = targetWeekly * effectiveWeeks
        $targetSemester = (int) round($targetWeekly * $effectiveWeeks);

        $diffWeekly = $realizedWeekly - $targetWeekly;

        $status = $diffWeekly === 0
            ? 'balanced'
            : ($diffWeekly > 0 ? 'overload' : 'underload');

        return [
            'teacher_id'        => $teacher->id,
            'name'              => $teacher->full_name,
            'target_weekly'     => $targetWeekly,
            'realized_weekly'   => $realizedWeekly,
            'diff_weekly'       => $diffWeekly,
            'effective_weeks'   => $effectiveWeeks,
            'target_semester'   => $targetSemester,
            'realized_semester' => $realizedSemester,
            'status'            => $status,
            'details'           => $details, // drilldown
        ];
    }

    /**
     * Dashboard kurikulum
     */
    public function curriculumDashboard(
        AcademicYear $year,
        float $effectiveWeeks,
        AcademicCalendarService $calendarService,
        ?int $teacherId = null
    ): array {
        $query = Teacher::withoutTrashed();
        
        if ($teacherId) {
            $query->where('id', $teacherId);
        }

        $rows = $query->get()
            ->map(fn ($t) => $this->compareTargetVsRealization($year, $t, $effectiveWeeks, $calendarService));

        return [
            'summary' => [
                'total'      => $rows->count(),
                'balanced'   => $rows->where('status', 'balanced')->count(),
                'overload'   => $rows->where('status', 'overload')->count(),
                'underload'  => $rows->where('status', 'underload')->count(),
            ],
            'rows' => $rows->values(),
        ];
    }
}
