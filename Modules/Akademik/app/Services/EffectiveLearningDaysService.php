<?php
namespace Modules\Akademik\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\Akademik\Models\AcademicEvent;
use Modules\Akademik\Models\AcademicYear;

class EffectiveLearningDaysService
{
    public function calculate(AcademicYear $year)
    {
        $period = CarbonPeriod::create(
            $year->start_date,
            $year->end_date
        );

        $schoolDays = $year->effective_school_days;

        $holidays = AcademicEvent::where('academic_year_id', $year->id)
            ->where('is_holiday', true)
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        $effectiveDays = 0;

        foreach ($period as $date) {
            $dayKey = strtolower($date->format('D')); // mon,tue,...

            if (!in_array($dayKey, $schoolDays)) continue;
            if (in_array($date->format('Y-m-d'), $holidays)) continue;

            $effectiveDays++;
        }

        return $effectiveDays;
    }
}