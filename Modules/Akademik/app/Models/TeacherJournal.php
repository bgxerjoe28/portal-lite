<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'teaching_schedule_id', 'date', 'topic', 
        'notes', 'status', 'cp_id', 'tp_id'
    ];

    public function schedule()
    {
        return $this->belongsTo(TeachingSchedule::class, 'teaching_schedule_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}