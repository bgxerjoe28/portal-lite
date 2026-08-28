<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'capacity',
        'location',
        'facilities',
        'description',
        'image',
        'images',
        'status',
        'is_reservable',
    ];

    protected $casts = [
        'facilities' => 'array',
        'images' => 'array',
        'capacity' => 'integer',
        'is_reservable' => 'boolean',
    ];

    public function reservations()
    {
        return $this->hasMany(FacilityReservation::class, 'room_id');
    }

    public function blackouts()
    {
        return $this->hasMany(FacilityBlackout::class, 'room_id');
    }

    public function damageReports()
    {
        return $this->hasMany(FacilityDamageReport::class, 'room_id');
    }
}
