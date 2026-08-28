<?php

namespace Modules\Akademik\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Panggil Model User Utama

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'nip', 'full_name',
        'gelar_depan', 'gelar_belakang',
        'gender', 'phone',
    ];

    // 1. Casting (Wajib)
    protected $casts = [
        'gender' => 'boolean',
    ];

    // 2. Appends (Agar field virtual ini terkirim ke JSON/Vue)
    protected $appends = ['gender_label', 'name_with_degree'];

    public function getNameWithDegreeAttribute()
    {
        $name = trim($this->attributes['full_name'] ?? '');
        $gelarDepan = trim($this->gelar_depan ?? '');
        $gelarBelakang = trim($this->gelar_belakang ?? '');

        // 1. Bersihkan gelar belakang dari akhir nama jika sudah terlanjur diketik manual oleh user
        if (!empty($gelarBelakang)) {
            $patternBelakang = preg_quote(rtrim($gelarBelakang, '.'), '/');
            $name = preg_replace('/,?\s*' . $patternBelakang . '\.?$/i', '', $name);
            $name = trim($name);
        }

        // 2. Bersihkan gelar depan dari awal nama jika sudah terlanjur diketik manual oleh user
        if (!empty($gelarDepan)) {
            $patternDepan = preg_quote(rtrim($gelarDepan, '.'), '/');
            $name = preg_replace('/^' . $patternDepan . '\.?\s*/i', '', $name);
            $name = trim($name);
        }

        // 3. Rangkai kembali dengan rapi
        if (!empty($gelarDepan)) {
            $name = $gelarDepan . ' ' . $name;
        }
        
        if (!empty($gelarBelakang)) {
            $separator = str_starts_with($gelarBelakang, ',') ? ' ' : ', ';
            $name = $name . $separator . $gelarBelakang;
        }
        
        return $name;
    }

    // Override kolom full_name bawaan
    public function getFullNameAttribute($value)
    {
        // Pengecualian untuk halaman manajemen/CRUD guru di admin agar value form (v-model) tetap asli
        if (request()->routeIs('admin.teachers.*')) {
            return $value;
        }

        // Di halaman lain (Siswa, Dasbor Guru, Laporan, dsb), otomatis tampilkan nama dengan gelar
        return $this->name_with_degree;
    }

    // 3. Accessor: Mengubah True/False jadi "L"/"P" untuk tampilan
    public function getGenderLabelAttribute()
    {
        return $this->gender ? 'L' : 'P';
    }

    // Relasi ke User Login
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Guru bisa jadi wali kelas
    public function homerooms()
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function agendaAttendances()
    {
        return $this->hasMany(AgendaAttendance::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function agendas()
    {
        return $this->hasMany(TeacherAgenda::class);
    }
}
