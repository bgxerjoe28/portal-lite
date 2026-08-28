<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Akademik\Models\Student;
use Modules\Cbt\Casts\JsonUnescapedUnicode;

class CbtStudentExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_exam_id',
        'student_id',
        'started_at',
        'submitted_at',
        'score',
        'status',
        'submit_type',
        'question_order',
        'options_order',
        'warning_count',
        'blocked_until',
        'is_blocked',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'float',
        'question_order' => JsonUnescapedUnicode::class,
        'options_order'  => JsonUnescapedUnicode::class,
        'warning_count' => 'integer',
        'blocked_until' => 'datetime',
        'is_blocked' => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(CbtExam::class, 'cbt_exam_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(CbtStudentAnswer::class, 'cbt_student_exam_id');
    }
}
