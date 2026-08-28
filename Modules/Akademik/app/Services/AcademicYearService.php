<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    public function getAll()
    {
        return AcademicYear::orderBy('created_at', 'desc')->get();
    }

    public function getActive(): ?AcademicYear
    {
        return AcademicYear::where('is_active', true)->first();
    }

    public function create(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data) {
            return AcademicYear::create($data);
        });
    }

    public function update(AcademicYear $year, array $data): AcademicYear
    {
        return DB::transaction(function () use ($year, $data) {
            $year->update($data);
            return $year;
        });
    }

    public function activate(AcademicYear $year): void
    {
        DB::transaction(function () use ($year) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            $year->update(['is_active' => true]);
        });
    }

    public function isDateWithinActiveYear(string $date): bool
    {
        $active = $this->getActive();

        if (!$active || !$active->start_date || !$active->end_date) {
            return false;
        }

        return $date >= $active->start_date && $date <= $active->end_date;
    }
}
