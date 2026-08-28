<?php

namespace Modules\Penugasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'question_id',
        'answer_text',
        'url_upload',
        'similarity_percentage',
        'score',
        'is_correct',
        'feedback',
    ];

    protected $casts = [
        'similarity_percentage' => 'float',
        'score' => 'float',
        'is_correct' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(AssignmentQuestion::class, 'question_id');
    }
}
