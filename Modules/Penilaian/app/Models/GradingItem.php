<?php

namespace Modules\Penilaian\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Akademik\Models\Classroom;

class GradingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_component_id',
        'classroom_id',
        'title',
        'date',
        'is_posted',
    ];

    protected $casts = [
        'date' => 'date',
        'is_posted' => 'boolean',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    // Relasi ke atas: Milik Komponen apa? (Tugas/UTS/UAS)
    public function component()
    {
        return $this->belongsTo(GradingComponent::class, 'grading_component_id');
    }

    // Relasi ke bawah: Punya banyak nilai siswa
    public function grades()
    {
        return $this->hasMany(StudentGrade::class, 'grading_item_id');
    }
    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class, 'grading_item_id');
    }
}
