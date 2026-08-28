<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


// use Modules\Akademik\Database\Factories\ScheduleFactory;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'religion_id',
        'quota',
    ];

    // --- RELASI KE SEMUA PIHAK ---

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

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

    // Relasi ke detail jadwal (hari-hari)
    public function details()
    {
        return $this->hasMany(ScheduleDetail::class);
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class);
    }
    public function attendances()
    {
        // Karena Schedule ada di modul Akademik dan Attendance di modul Kesiswaan
        return $this->hasMany(\Modules\Kesiswaan\Models\Attendance::class, 'schedule_id');
    }
}
