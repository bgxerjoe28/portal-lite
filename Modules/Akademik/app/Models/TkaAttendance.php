<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Akademik\Database\Factories\TkaAttendanceFactory;

class TkaAttendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tka_session_id',
        'student_id',
        'status',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(TkaSession::class, 'tka_session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // protected static function newFactory(): TkaAttendanceFactory
    // {
    //     // return TkaAttendanceFactory::new();
    // }
}
