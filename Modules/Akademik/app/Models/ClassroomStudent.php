<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassroomStudent extends Model
{
    use HasFactory;

    protected $table = 'classroom_students';

    // Konstanta Status Kenaikan Kelas
    public const STATUS_ACTIVE = 'aktif';
    public const STATUS_PINDAH = 'pindah';
    public const STATUS_KELUAR = 'keluar';
    public const STATUS_LULUS = 'lulus';
    public const STATUS_PROMOTED = 'promoted'; // Naik kelas
    public const STATUS_RETAINED = 'retained'; // Tinggal kelas

    // Pastikan kolom-kolom ini ada di database Anda
    protected $fillable = [
        'academic_year_id',
        'classroom_id',
        'student_id',
        'status', // active, moved, graduated
    ];

    // --- RELASI ---

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
