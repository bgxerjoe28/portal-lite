<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingAgenda extends Model
{
    protected $appends = ['agenda_date'];

    protected $fillable = [
        'academic_year_id',
        'schedule_id',
        'schedule_detail_id',
        'start_slot',
        'end_slot',
        'teacher_id',
        'classroom_id',
        'subject_id',
        'learning_objective_tp_id',
        'date',
        'day',
        'materi_pembelajaran',
        'keterangan',
    ];

    /* ================= RELATIONS ================= */

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function tp()
    {
        return $this->belongsTo(LearningObjectiveTP::class, 'learning_objective_tp_id');
    }

    public function attendances()
    {
        return $this->hasMany(AgendaAttendance::class);
    }

    public function attachments()
    {
        return $this->hasMany(AgendaAttachment::class);
    }

    public function getAgendaDateAttribute()
    {
        return $this->date;
    }

    public function scheduleDetail()
    {
        return $this->belongsTo(ScheduleDetail::class, 'schedule_detail_id');
    }
}
