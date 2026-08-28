<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityDamageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_code',
        'reservation_id',
        'asset_id',
        'room_id',
        'reported_by',
        'damage_type',
        'description',
        'evidence_photos',
        'action_plan',
        'compensation_fee',
        'is_compensated',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'evidence_photos' => 'array',
        'compensation_fee' => 'decimal:2',
        'is_compensated' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->report_code)) {
                $count = self::whereYear('created_at', date('Y'))
                    ->whereMonth('created_at', date('m'))
                    ->count() + 1;
                $report->report_code = 'DMG-' . date('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function reservation()
    {
        return $this->belongsTo(FacilityReservation::class, 'reservation_id');
    }

    public function asset()
    {
        return $this->belongsTo(FacilityAsset::class, 'asset_id');
    }

    public function room()
    {
        return $this->belongsTo(FacilityRoom::class, 'room_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
