<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaAttendance extends Model
{
    protected $fillable = [
        'teaching_agenda_id',
        'student_id',
        'is_present',
        'note',
    ];

    public function agenda()
    {
        return $this->belongsTo(TeachingAgenda::class, 'teaching_agenda_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}