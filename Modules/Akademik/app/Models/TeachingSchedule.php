<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeachingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id', 'classroom_id', 'subject_id', 
        'teacher_id', 'day_of_week', 'start_time', 'end_time'
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // Relasi ke Jurnal (Agenda)
    public function journals()
    {
        return $this->hasMany(TeacherJournal::class);
    }
}