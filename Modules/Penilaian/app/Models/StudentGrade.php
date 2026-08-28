<?php

namespace Modules\Penilaian\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Akademik\Models\Student;

class StudentGrade extends Model
{
    protected $fillable = [
        'grading_item_id',
        'student_id',
        'score',
        'note',
    ];

    public function gradingItem()
    {
        return $this->belongsTo(GradingItem::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
