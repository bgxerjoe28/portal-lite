<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbtAnalysisJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_exam_id',
        'cbt_bank_id',
        'job_type',
        'status',
        'total_participants',
        'progress_percent',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
