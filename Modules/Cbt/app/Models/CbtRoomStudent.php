<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Akademik\Models\Student;

class CbtRoomStudent extends Model
{
    protected $table = 'cbt_room_students';

    protected $fillable = [
        'cbt_room_id',
        'student_id',
        'seat_number',
    ];

    public function room()
    {
        return $this->belongsTo(CbtRoom::class, 'cbt_room_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
