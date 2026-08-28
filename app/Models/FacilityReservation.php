<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Kesiswaan\Models\Extracurricular;

class FacilityReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_code',
        'user_id',
        'extracurricular_id',
        'room_id',
        'type',
        'title',
        'purpose',
        'proposal_file_path',
        'start_time',
        'end_time',
        'participant_count',
        'requires_coach_approval',
        'coach_user_id',
        'stage1_status',
        'stage1_by',
        'stage1_notes',
        'stage1_at',
        'stage2_status',
        'stage2_by',
        'stage2_notes',
        'stage2_at',
        'status',
        'handover_by',
        'handover_at',
        'handover_notes',
        'return_by',
        'return_at',
        'return_notes',
        'return_condition_summary',
        'qr_token',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'stage1_at' => 'datetime',
        'stage2_at' => 'datetime',
        'handover_at' => 'datetime',
        'return_at' => 'datetime',
        'requires_coach_approval' => 'boolean',
        'participant_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($res) {
            if (empty($res->reservation_code)) {
                $count = self::whereYear('created_at', date('Y'))
                    ->whereMonth('created_at', date('m'))
                    ->count() + 1;
                $res->reservation_code = 'SAR-' . date('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            if (empty($res->qr_token)) {
                $res->qr_token = 'PERMIT-' . Str::upper(Str::random(12));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class, 'extracurricular_id');
    }

    public function room()
    {
        return $this->belongsTo(FacilityRoom::class, 'room_id');
    }

    public function coachUser()
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function stage1Approver()
    {
        return $this->belongsTo(User::class, 'stage1_by');
    }

    public function stage2Approver()
    {
        return $this->belongsTo(User::class, 'stage2_by');
    }

    public function handoverUser()
    {
        return $this->belongsTo(User::class, 'handover_by');
    }

    public function returnUser()
    {
        return $this->belongsTo(User::class, 'return_by');
    }

    public function reservationAssets()
    {
        return $this->hasMany(FacilityReservationAsset::class, 'reservation_id');
    }

    public function assets()
    {
        return $this->belongsToMany(FacilityAsset::class, 'facility_reservation_assets', 'reservation_id', 'asset_id')
            ->withPivot(['id', 'checkout_condition', 'return_condition', 'notes'])
            ->withTimestamps();
    }

    public function damageReports()
    {
        return $this->hasMany(FacilityDamageReport::class, 'reservation_id');
    }
}
