<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Akademik\Database\Factories\TkaStudentFactory;

class TkaStudent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tka_subject_id',
        'student_id',
        'academic_year_id',
        'status',
    ];

    public function tkaSubject()
    {
        return $this->belongsTo(TkaSubject::class, 'tka_subject_id');
    }

    public function subject()
    {
        return $this->belongsTo(TkaSubject::class, 'tka_subject_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    // protected static function newFactory(): TkaStudentFactory
    // {
    //     // return TkaStudentFactory::new();
    // }
}
