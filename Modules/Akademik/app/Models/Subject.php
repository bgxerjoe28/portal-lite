<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'is_religion'];

    protected $casts = [
        'is_religion' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(TeachingSchedule::class);
    }

    // Relasi ke Aturan Main (Mapping)
    public function mappings()
    {
        return $this->hasMany(SubjectMapping::class);
    }
    public function assignments()
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }

    // --- TAMBAHKAN FUNGSI BOOTED INI ---
    protected static function booted()
    {
        // Saat Subject dihapus (Soft Delete)
        static::deleted(function ($subject) {
            // Hapus semua mapping terkait
            $subject->mappings()->delete();
        });

        // Saat Subject dikembalikan (Restore)
        static::restored(function ($subject) {
            // Kembalikan semua mapping terkait
            $subject->mappings()->withTrashed()->restore();
        });

        // Saat Subject dihapus PERMANEN (Force Delete)
        static::forceDeleted(function ($subject) {
            // Hapus mapping selamanya
            $subject->mappings()->forceDelete();
        });
    }
}
