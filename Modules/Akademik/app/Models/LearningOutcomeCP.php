<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\JsonUnescapedUnicode;

// use Modules\Akademik\Database\Factories\LearningOutcomeCPFactory;

class LearningOutcomeCP extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'learning_outcomes_cp';

    protected $fillable = [
        'subjects_id',
        'fase',
        'judul_cp',
        'rumusan_cp',
        'kata_kunci',
    ];

    protected $casts = [
        'kata_kunci' => JsonUnescapedUnicode::class,
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjects_id');
    }

    public function tps()
    {
        return $this->hasMany(LearningObjectiveTP::class, 'cp_id');
    }
}
