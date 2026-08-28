<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_journal_id', 'student_id', 
        'status', 'minutes_late', 'notes'
    ];

    public function journal()
    {
        return $this->belongsTo(TeacherJournal::class, 'teacher_journal_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}