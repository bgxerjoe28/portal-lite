<?php

namespace Modules\Penugasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Subject;
use Modules\Penilaian\Models\GradingComponent;
use Modules\Penilaian\Models\GradingItem;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'description',
        'academic_year_id',
        'teacher_id',
        'classroom_id',
        'subject_id',
        'grading_component_id',
        'start_at',
        'due_at',
        'is_published',
        'is_grades_published',
        'grading_item_id',
        'prerequisite_assignment_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'due_at' => 'datetime',
        'is_published' => 'boolean',
        'is_grades_published' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradingComponent()
    {
        return $this->belongsTo(GradingComponent::class);
    }

    public function gradingItem()
    {
        return $this->belongsTo(GradingItem::class);
    }

    public function prerequisite()
    {
        return $this->belongsTo(Assignment::class, 'prerequisite_assignment_id');
    }

    public function questions()
    {
        return $this->hasMany(AssignmentQuestion::class)->orderBy('sort_order', 'asc');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
