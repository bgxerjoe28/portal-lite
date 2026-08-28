<?php

namespace Modules\Akademik\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    // Hapus classroom_id dari fillable
    protected $fillable = [
        'user_id', 'full_name', 'religion_id', 'nis', 'nisn',
        'gender', 'birth_place', 'birth_date', 'phone', 'address',
        
        'nik', 'no_kk', 'akta_no', 'citizenship', 'special_needs',
        'rt', 'rw', 'dusun', 'kelurahan', 'kecamatan', 'postal_code',
        'latitude', 'longitude', 'residence_type', 'transportation', 'child_order',
        
        'father_name', 'father_deceased', 'father_nik', 'father_birth_year',
        'father_education', 'father_job', 'father_income', 'father_special_needs', 'father_phone',
        
        'mother_name', 'mother_deceased', 'mother_nik', 'mother_birth_year',
        'mother_education', 'mother_job', 'mother_income', 'mother_special_needs', 'mother_phone',
        
        'guardian_name', 'guardian_nik', 'guardian_birth_year',
        'guardian_education', 'guardian_job', 'guardian_income', 'guardian_phone',
        
        'height', 'weight', 'head_circumference',
        'distance_to_school_km', 'travel_time_minutes', 'sibling_count', 'periodik_phone',
        
        'prev_school_type', 'prev_school_status', 'prev_school_name',
        
        'jenis_kejuaraan', 'nilai', 'jarak', 'umur', 'nilai_akhir',
        
        'file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other'
    ];

    // 1. Casting (Wajib)
    protected $casts = [
        'gender' => 'boolean',
    ];

    // 2. Appends (Agar field virtual ini terkirim ke JSON/Vue)
    protected $appends = ['gender_label', 'name'];

    // 3. Accessor: Mengubah True/False jadi "L"/"P" untuk tampilan
    public function getGenderLabelAttribute()
    {
        return $this->gender ? 'L' : 'P';
    }

    // Alias 'name' mengarah ke 'full_name' agar kompatibel dengan pemanggilan $student->name ataupun di Vue (student.name)
    public function getNameAttribute()
    {
        return $this->full_name;
    }

    public function getFatherNameAttribute($value)
    {
        try {
            return $value ? \Illuminate\Support\Facades\Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getMotherNameAttribute($value)
    {
        try {
            return $value ? \Illuminate\Support\Facades\Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getGuardianNameAttribute($value)
    {
        try {
            return $value ? \Illuminate\Support\Facades\Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // RELASI MANY-TO-MANY (Riwayat - diurutkan dari tahun ajaran terbaru & id terbaru)
    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_students')
            ->withPivot('academic_year_id', 'status')
            ->orderByPivot('academic_year_id', 'desc')
            ->orderByPivot('id', 'desc')
            ->withTimestamps();
    }

    // HELPER: Ambil Kelas di Tahun Ajaran Aktif Saja
    public function currentClassroom()
    {
        return $this->hasOneThrough(
            Classroom::class,           // Target
            ClassroomStudent::class, // Pivot Model
            'student_id',               // FK di Pivot
            'id',                       // FK di Classroom
            'id',                       // PK di Student
            'classroom_id'              // FK di Pivot ke Classroom
        )->where('classroom_students.status', 'aktif')
         ->orderBy('classroom_students.academic_year_id', 'desc')
         ->orderBy('classroom_students.id', 'desc');
    }

    // Relasi kelas aktif saat ini (alias dari currentClassroom agar bisa di-load via with('classroom'))
    public function classroom()
    {
        return $this->currentClassroom();
    }

    // Relasi ke Agama
    public function religion()
    {
        return $this->belongsTo(Religion::class, 'religion_id');
    }

    public function classInYear($academicYearId)
    {
        return $this->classrooms()
            ->wherePivot('academic_year_id', $academicYearId)
            ->wherePivot('status', 'aktif')
            ->first();
    }

    public function agendaAttendances()
    {
        return $this->hasMany(AgendaAttendance::class);
    }

    public function permits()
    {
        return $this->hasMany(StudentPermit::class, 'student_id');
    }

    public function studentViolations()
    {
        return $this->hasMany(\Modules\Kesiswaan\Models\StudentViolation::class, 'student_id');
    }

    public function sanctions()
    {
        return $this->hasMany(\Modules\Kesiswaan\Models\StudentSanction::class, 'student_id');
    }

    public function merits()
    {
        return $this->hasMany(\Modules\Kesiswaan\Models\StudentMerit::class, 'student_id');
    }

    public function pointHistories()
    {
        return $this->hasMany(\Modules\Kesiswaan\Models\StudentPointHistory::class, 'student_id');
    }

    /**
     * Hitung saldo poin terkini siswa.
     */
    public function currentPointBalance(): int
    {
        if (class_exists('Modules\Kesiswaan\Models\StudentPointHistory')) {
            return \Modules\Kesiswaan\Models\StudentPointHistory::currentBalance($this->id);
        }
        return 0;
    }
}
