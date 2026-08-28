<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Akademik\Database\Factories\LearningObjectiveTPFactory;

class LearningObjectiveTP extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'learning_objectives_tp';

    protected $fillable = [
        'cp_id',
        'kode_tp',
        'nomor_tp',
        'rumusan_tp',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cp()
    {
        return $this->belongsTo(LearningOutcomeCP::class, 'cp_id');
    }
    public function agendas()
    {
        return $this->hasMany(TeachingAgenda::class, 'learning_objective_tp_id');
    }

}
