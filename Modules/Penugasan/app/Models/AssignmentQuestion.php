<?php

namespace Modules\Penugasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\JsonUnescapedUnicode;

class AssignmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'type',
        'question_text',
        'options',
        'correct_answer',
        'keywords',
        'allow_url_upload',
        'max_score',
        'sort_order',
    ];

    protected $casts = [
        'options'  => JsonUnescapedUnicode::class,
        'keywords' => JsonUnescapedUnicode::class,
        'allow_url_upload' => 'boolean',
        'max_score' => 'float',
        'sort_order' => 'integer',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
