<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\Classroom;
use Modules\Penilaian\Models\GradingComponent;

class CbtExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_bank_id',
        'teacher_id',
        'title',
        'duration',
        'start_time',
        'end_time',
        'shuffle_questions',
        'shuffle_options',
        'must_complete_all',
        'is_independent',
        'is_active',
        'grading_component_id',
        'grading_item_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'must_complete_all' => 'boolean',
        'is_independent' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function bank()
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'cbt_exam_classrooms', 'cbt_exam_id', 'classroom_id')
            ->withTimestamps();
    }

    public function studentExams()
    {
        return $this->hasMany(CbtStudentExam::class, 'cbt_exam_id');
    }


    public function gradingComponent()
    {
        return $this->belongsTo(GradingComponent::class, 'grading_component_id');
    }
}
