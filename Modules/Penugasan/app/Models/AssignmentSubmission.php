<?php

namespace Modules\Penugasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Akademik\Models\Student;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'status',
        'submitted_at',
        'total_score',
        'teacher_notes',
        'is_editable',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'total_score' => 'float',
        'is_editable' => 'boolean',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function answers()
    {
        return $this->hasMany(AssignmentAnswer::class, 'submission_id');
    }
}
