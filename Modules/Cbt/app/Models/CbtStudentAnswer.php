<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cbt\Casts\JsonUnescapedUnicode;

class CbtStudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_student_exam_id',
        'cbt_question_id',
        'selected_answer',
        'is_correct',
        'points_earned',
        'is_doubtful',
    ];

    protected $casts = [
        'selected_answer' => JsonUnescapedUnicode::class,
        'is_correct' => 'boolean',
        'points_earned' => 'float',
        'is_doubtful' => 'boolean',
    ];

    public function studentExam()
    {
        return $this->belongsTo(CbtStudentExam::class, 'cbt_student_exam_id');
    }

    public function question()
    {
        return $this->belongsTo(CbtQuestion::class, 'cbt_question_id');
    }
}
