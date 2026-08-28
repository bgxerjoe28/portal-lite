<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleDetail extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'day', 'start_slot', 'end_slot'];

    // Atribut Virtual: Menghitung Durasi (JP)
    public function getDurationAttribute()
    {
        return $this->end_slot - $this->start_slot + 1;
    }

    protected static function booted()
    {
        static::created(function ($detail) {
            TeachingAgenda::where('schedule_id', $detail->schedule_id)
                ->whereNull('start_slot')
                ->whereRaw('LOWER(day) = ?', [strtolower($detail->day)])
                ->update([
                    'schedule_detail_id' => $detail->id,
                    'start_slot' => $detail->start_slot,
                    'end_slot' => $detail->end_slot,
                ]);
        });
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
