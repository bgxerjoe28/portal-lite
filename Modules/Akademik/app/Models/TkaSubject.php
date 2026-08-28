<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Akademik\Database\Factories\TkaSubjectFactory;

class TkaSubject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'academic_year_id',
        'name',
        'code',
        'is_active',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function sessions()
    {
        return $this->hasMany(TkaSession::class, 'tka_subject_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'tka_students', 'tka_subject_id', 'student_id')
                    ->withPivot(['id', 'academic_year_id', 'status'])
                    ->withTimestamps();
    }

    // protected static function newFactory(): TkaSubjectFactory
    // {
    //     // return TkaSubjectFactory::new();
    // }
}
