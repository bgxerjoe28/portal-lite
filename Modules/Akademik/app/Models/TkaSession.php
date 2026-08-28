<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Akademik\Database\Factories\TkaSessionFactory;

class TkaSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tka_subject_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function tkaSubject()
    {
        return $this->belongsTo(TkaSubject::class, 'tka_subject_id');
    }

    public function attendances()
    {
        return $this->hasMany(TkaAttendance::class, 'tka_session_id');
    }

    // protected static function newFactory(): TkaSessionFactory
    // {
    //     // return TkaSessionFactory::new();
    // }
}
