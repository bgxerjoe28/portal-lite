<?php

namespace Modules\Penilaian\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Akademik\Models\AcademicYear; // Import dari modul sebelah
use Modules\Akademik\Models\Subject;

class GradingComponent extends Model
{
    protected $fillable = ['name', 'weight', 'subject_id', 'academic_year_id', 'teacher_id', 'sort_order', 'passing_grade'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function items()
    {
        return $this->hasMany(GradingItem::class, 'grading_component_id');
    }

    public function gradingItems()
    {
        return $this->hasMany(GradingItem::class, 'grading_component_id');
    }
}
