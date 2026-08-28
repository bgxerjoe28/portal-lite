<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id', 'teacher_id', 'name', 'level', 'major',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Wali Kelas
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'classroom_students', // Nama Tabel Pivot
            'classroom_id',       // Foreign Key di Pivot untuk model ini
            'student_id'          // Foreign Key di Pivot untuk model seberang
        )
            ->withPivot(['status', 'academic_year_id']) // Ambil kolom tambahan di pivot
            ->withTimestamps();
    }

    // Relasi langsung ke Pivot (untuk akses data pivot spesifik)
    public function classroomStudents()
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    public function agendas()
    {
        return $this->hasMany(TeacherAgenda::class);
    }
    public function assignments()
    {
        // Hubungkan Classroom ke Schedule lewat classroom_id
        return $this->hasMany(Schedule::class, 'classroom_id');
    }
}
