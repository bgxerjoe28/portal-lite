<?php

namespace Modules\Akademik\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Modules\Akademik\Models\AcademicEvent;
use Modules\Akademik\Models\AcademicYear;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class AcademicCalendarService
{
    private function mapDayToKey(Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
        };
    }
    /**
     * Ambil event kalender (range-aware)
     */
    public function getEvents(AcademicYear $year)
    {
        return AcademicEvent::where('academic_year_id', $year->id)
            ->orderBy('start_date')
            ->get()
            ->map(fn ($e) => [
                'id'         => $e->id,
                'title'      => $e->title,
                'type'       => $e->type,
                'is_holiday' => (bool) $e->is_holiday,
                'start_date' => $e->start_date,
                'end_date'   => $e->end_date,
            ]);
    }

    /**
     * Simpan event (single & range disatukan)
     */
    public function storeEvent(array $data): void
    {
        AcademicEvent::create([
            'academic_year_id' => $data['academic_year_id'],
            'title'            => $data['title'],
            'type'             => $data['type'],
            'is_holiday'       => $data['is_holiday'] ?? false,
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
        ]);
    }

    /**
     * Validasi event harus di dalam semester aktif
     */
    public function validateRangeInSemester(
        AcademicYear $year,
        Carbon $start,
        Carbon $end
    ): void {
        if (
            $start->lt($year->start_date) ||
            $end->gt($year->end_date)
        ) {
            throw new InvalidArgumentException(
                'Rentang event harus berada dalam periode semester aktif.'
            );
        }

        if ($start->gt($end)) {
            throw new InvalidArgumentException(
                'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.'
            );
        }
    }

    /**
     * Hitung hari efektif KBM (range-aware)
     */
    public function calculateEffectiveDays(AcademicYear $year): int
    {
        return $this->calculateTotalEffectiveSchoolDays($year);
    }
   public function calculateTeachingEffectiveDays(
        AcademicYear $academicYear,
        Collection $schedules
    ): int
    {
        if (! $academicYear->start_date || ! $academicYear->end_date) {
            Log::warning('[HEP] Academic year tanpa tanggal', [
                'academic_year_id' => $academicYear->id
            ]);
            return 0;
        }

        Log::info('[HEP] START', [
            'academic_year_id' => $academicYear->id,
            'start_date' => $academicYear->start_date,
            'end_date' => $academicYear->end_date,
        ]);

        // 1️⃣ Hari sekolah efektif
        $schoolDays = collect(
            $academicYear->school_days
                ?? ['mon','tue','wed','thu','fri','sat']
        )->values();

        Log::info('[HEP] School days rule', $schoolDays->toArray());

        // 2️⃣ Hari guru benar-benar mengajar
        $teachingDays = $schedules
            ->map(fn ($d) => $this->normalizeDay($d->day))
            ->filter()
            ->unique()
            ->values();

        Log::info('[HEP] Teaching days (from schedule)', $teachingDays->toArray());

        if ($teachingDays->isEmpty()) {
            Log::warning('[HEP] No teaching days found');
            return 0;
        }

        // 3️⃣ Tanggal Non-KBM
        $nonKbmDates = AcademicEvent::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('is_holiday', true)
            ->get(['start_date','end_date'])
            ->flatMap(fn ($event) =>
                CarbonPeriod::create(
                    $event->start_date,
                    $event->end_date
                )->toArray()
            )
            ->map(fn (Carbon $d) => $d->toDateString())
            ->unique()
            ->values();

        Log::info('[HEP] Non-KBM dates count', [
            'total' => $nonKbmDates->count(),
        ]);

        // 4️⃣ Iterasi tanggal akademik
        $period = CarbonPeriod::create(
            $academicYear->start_date,
            $academicYear->end_date
        );

        $totalDays = 0;
        $effectiveDays = 0;

        foreach ($period as $date) {
            $totalDays++;

            $dayKey = strtolower(substr($date->format('D'), 0, 3)); // mon
            $ymd = $date->toDateString();

            // a. Bukan hari sekolah
            if (! $schoolDays->contains($dayKey)) {
                Log::debug('[HEP] SKIP non-school-day', [
                    'date' => $ymd,
                    'day' => $dayKey
                ]);
                continue;
            }

            // b. Libur / Non-KBM
            if ($nonKbmDates->contains($ymd)) {
                Log::debug('[HEP] SKIP holiday', [
                    'date' => $ymd,
                    'day' => $dayKey
                ]);
                continue;
            }

            // c. Tidak ada jadwal mengajar
            if (! $teachingDays->contains($dayKey)) {
                Log::debug('[HEP] SKIP no-teaching', [
                    'date' => $ymd,
                    'day' => $dayKey
                ]);
                continue;
            }

            // ✅ Hari efektif
            $effectiveDays++;

            Log::debug('[HEP] COUNT effective day', [
                'date' => $ymd,
                'day' => $dayKey
            ]);
        }

        Log::info('[HEP] RESULT', [
            'total_calendar_days' => $totalDays,
            'effective_days' => $effectiveDays,
        ]);

        return $effectiveDays;
    }

    /**
     * Ambil semua tanggal libur (Non-KBM) dalam tahun ajaran
     */
    protected function getHolidayDates(AcademicYear $year): array
    {
        $events = AcademicEvent::query()
            ->where('academic_year_id', $year->id)
            ->where('is_holiday', true)
            ->get(['start_date', 'end_date']);

        $dates = [];

        foreach ($events as $event) {
            $period = CarbonPeriod::create(
                Carbon::parse($event->start_date),
                Carbon::parse($event->end_date)
            );

            foreach ($period as $date) {
                $dates[] = $date->toDateString();
            }
        }

        return array_unique($dates);
    }
    public function calculateTotalEffectiveSchoolDays(
        AcademicYear $academicYear
    ): int {
        if (! $academicYear->start_date || ! $academicYear->end_date) {
            return 0;
        }

        // 1️⃣ Aturan hari sekolah (mon–fri / mon–sat)
        $schoolDays = collect(
            $academicYear->school_days
                ?? ['mon','tue','wed','thu','fri','sat']
        );

        // 2️⃣ Semua tanggal Non-KBM
        $nonKbmDates = AcademicEvent::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('is_holiday', true)
            ->get(['start_date','end_date'])
            ->flatMap(fn ($event) =>
                CarbonPeriod::create(
                    $event->start_date,
                    $event->end_date
                )->toArray()
            )
            ->map(fn (Carbon $d) => $d->toDateString())
            ->unique();

        // 3️⃣ Iterasi tanggal tahun ajaran
        $period = CarbonPeriod::create(
            $academicYear->start_date,
            $academicYear->end_date
        );

        $total = 0;

        foreach ($period as $date) {
            $dayKey = strtolower(substr($date->format('D'), 0, 3));
            $ymd    = $date->toDateString();

            // a. Bukan hari sekolah
            if (! $schoolDays->contains($dayKey)) {
                continue;
            }

            // b. Libur / Non-KBM
            if ($nonKbmDates->contains($ymd)) {
                continue;
            }

            $total++;
        }

        return $total;
    }
    public function calculateEffectiveWeeks(AcademicYear $year): float
    {
        $effectiveDays = $this->calculateTotalEffectiveSchoolDays($year);

        $schoolDaysPerWeek = count(
            $year->school_days ?? ['mon','tue','wed','thu','fri']
        );

        return $schoolDaysPerWeek > 0
            ? round($effectiveDays / $schoolDaysPerWeek, 1)
            : 0;
    }

    public function deleteEvent(int $id): void
    {
        AcademicEvent::findOrFail($id)->delete();
    }
    public function normalizeDay(?string $day): ?string
    {
        if (!$day) return null;

        $day = strtolower(trim($day));

        return match ($day) {
            'senin', 'mon', 'monday'     => 'mon',
            'selasa', 'tue', 'tuesday'   => 'tue',
            'rabu', 'wed', 'wednesday'   => 'wed',
            'kamis', 'thu', 'thursday'   => 'thu',
            'jumat', 'fri', 'friday'     => 'fri',
            'sabtu', 'sat', 'saturday'   => 'sat',
            default => null,
        };
    }
    public function countEffectiveOccurrencesByDayKeys(
        AcademicYear $year,
        Collection $dayKeys // isinya: ['mon','wed',...]
    ): array {
        if (! $year->start_date || ! $year->end_date) return [];

        $schoolDays = collect($year->school_days ?? ['mon','tue','wed','thu','fri'])
            ->filter()
            ->values();

        // Non-KBM dates
        $nonKbmDates = AcademicEvent::query()
            ->where('academic_year_id', $year->id)
            ->where('is_holiday', true)
            ->get(['start_date','end_date'])
            ->flatMap(fn ($event) => CarbonPeriod::create($event->start_date, $event->end_date)->toArray())
            ->map(fn (Carbon $d) => $d->toDateString())
            ->unique();

        $targets = $dayKeys->filter()->unique()->values();
        if ($targets->isEmpty()) return [];

        $counts = $targets->mapWithKeys(fn ($d) => [$d => 0])->toArray();

        $period = CarbonPeriod::create($year->start_date, $year->end_date);

        foreach ($period as $date) {
            $dayKey = strtolower(substr($date->format('D'), 0, 3)); // mon
            $ymd    = $date->toDateString();

            if (! $schoolDays->contains($dayKey)) continue;
            if ($nonKbmDates->contains($ymd)) continue;
            if (! $targets->contains($dayKey)) continue;

            $counts[$dayKey] = ($counts[$dayKey] ?? 0) + 1;
        }

        return $counts; // contoh: ['mon'=>20,'wed'=>21]
    }

}
