<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Casts\JsonUnescapedUnicode;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'semester', 
        'is_active', 
        'start_date', 
        'end_date', 
        'school_days'
    ];

    protected $casts = [
        'start_date'  => 'date:Y-m-d',
        'end_date'    => 'date:Y-m-d',
        'school_days' => JsonUnescapedUnicode::class,
    ];

    // Relasi: Satu tahun ajaran punya banyak kelas
    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
    public function getEffectiveSchoolDaysAttribute()
    {
        return $this->school_days ?? ['mon','tue','wed','thu','fri'];
    }
}