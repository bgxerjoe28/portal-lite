<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicEvent extends Model
{
    protected $fillable = [
        'academic_year_id',
        'start_date',   
        'end_date',
        'title',
        'type',
        'is_holiday',   
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'is_holiday' => 'boolean',
    ];

    public function isHoliday(): bool
    {
        return (bool) $this->is_holiday;
    }
}
