<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbtIrtItemParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_exam_id',
        'cbt_bank_id',
        'cbt_question_id',
        'model_type',
        'difficulty_b',
        'discrimination_a',
        'guessing_c',
        'infit_mnsq',
        'outfit_mnsq',
        'calculated_at',
    ];

    protected $casts = [
        'difficulty_b' => 'float',
        'discrimination_a' => 'float',
        'guessing_c' => 'float',
        'infit_mnsq' => 'float',
        'outfit_mnsq' => 'float',
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
