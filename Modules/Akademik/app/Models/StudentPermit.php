<?php

namespace Modules\Akademik\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Akademik\Database\Factories\StudentPermitFactory;

class StudentPermit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'date',
        'student_id',
        'permit_type',
        'start_slot',
        'end_slot',
        'reason',
        'teacher_id',
        'academic_year_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relasi ke data Siswa
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relasi ke data Guru/Staff yang menginput
     */
    public function recorder()
    {
        // 🔑 KUNCI: Pointing ke User menggunakan kolom teacher_id
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Accessor: Gabungan Logika untuk menampilkan nama
     */
    public function getDisplayNameAttribute()
    {
        // Jika user yang mencatat punya profil Guru, ambil nama dari tabel Guru
        // Jika tidak (Staf), ambil nama dari tabel User
        return $this->recorder?->teacher?->full_name ?? $this->recorder?->name;
    }

    // Daftarkan accessor agar terkirim ke Vue
    protected $appends = ['display_name'];
    // protected static function newFactory(): StudentPermitFactory
    // {
    //     // return StudentPermitFactory::new();
    // }
}
