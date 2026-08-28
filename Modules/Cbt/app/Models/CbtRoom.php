<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Akademik\Models\Student;

class CbtRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'cbt_room_students', 'cbt_room_id', 'student_id')
            ->withPivot('seat_number')
            ->withTimestamps();
    }

    public function roomStudents()
    {
        return $this->hasMany(CbtRoomStudent::class, 'cbt_room_id');
    }

}
