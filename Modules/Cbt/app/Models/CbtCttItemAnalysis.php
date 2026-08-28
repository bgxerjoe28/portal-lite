<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbtCttItemAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_exam_id',
        'cbt_bank_id',
        'cbt_question_id',
        'difficulty_index',
        'discrimination_index',
        'point_biserial',
        'distractor_stats',
        'calculated_at',
    ];

    protected $casts = [
        'difficulty_index' => 'float',
        'discrimination_index' => 'float',
        'point_biserial' => 'float',
        'distractor_stats' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(CbtExam::class, 'cbt_exam_id');
    }

    public function bank()
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }

    public function question()
    {
        return $this->belongsTo(CbtQuestion::class, 'cbt_question_id');
    }
}
