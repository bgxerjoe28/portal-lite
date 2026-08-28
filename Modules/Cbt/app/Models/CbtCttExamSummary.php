<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbtCttExamSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_exam_id',
        'cbt_bank_id',
        'cronbach_alpha',
        'mean_score',
        'median_score',
        'std_deviation',
        'min_score',
        'max_score',
        'total_participants',
        'calculated_at',
    ];

    protected $casts = [
        'cronbach_alpha' => 'float',
        'mean_score' => 'float',
        'median_score' => 'float',
        'std_deviation' => 'float',
        'min_score' => 'float',
        'max_score' => 'float',
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
}
