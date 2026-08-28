<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\Subject;

class CbtBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'name',
        'description',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function questions()
    {
        return $this->hasMany(CbtQuestion::class, 'cbt_bank_id');
    }

    public function exams()
    {
        return $this->hasMany(CbtExam::class, 'cbt_bank_id');
    }
}
