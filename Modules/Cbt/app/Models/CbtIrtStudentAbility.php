<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbtIrtStudentAbility extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_student_exam_id',
        'theta',
        'standard_error',
        'scaled_score',
        'percentile',
        'calculated_at',
    ];

    protected $casts = [
        'theta' => 'float',
        'standard_error' => 'float',
        'scaled_score' => 'float',
        'percentile' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function studentExam()
    {
        return $this->belongsTo(CbtStudentExam::class, 'cbt_student_exam_id');
    }
}
