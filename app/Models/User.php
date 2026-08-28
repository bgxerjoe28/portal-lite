<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\Student;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes, Impersonate, LogsActivity;

    /**
     * Hanya admin yang boleh melakukan impersonation.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Admin tidak boleh di-impersonate oleh siapapun.
     */
    public function canBeImpersonated(): bool
    {
        return !$this->hasRole('admin');
    }

    protected $appends = ['avatar_url'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'password_must_change',
        'last_login_at', 
        'last_login_ip'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke data profil Guru (Opsional, tapi bagus untuk konsistensi)
    public function teacher()
    {
        //return $this->hasOne(Teacher::class);
        return $this->hasOne(Teacher::class, 'user_id');
    }
    public function student()
    {
        return $this->hasOne(Student::class);
    }
    public function getTeacherIdAttribute()
    {
        return $this->teacher?->id;
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar_path ? \Illuminate\Support\Facades\Storage::url($this->avatar_path) : null;
    }
}
