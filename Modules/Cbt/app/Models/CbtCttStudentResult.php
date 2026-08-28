<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbtCttStudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_student_exam_id',
        'raw_score',
        'max_score',
        'percentage',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'rank',
        'calculated_at',
    ];

    protected $casts = [
        'raw_score' => 'float',
        'max_score' => 'float',
        'percentage' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function studentExam()
    {
        return $this->belongsTo(CbtStudentExam::class, 'cbt_student_exam_id');
    }
}
