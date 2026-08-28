<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityReservationAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'asset_id',
        'checkout_condition',
        'return_condition',
        'notes',
    ];

    public function reservation()
    {
        return $this->belongsTo(FacilityReservation::class, 'reservation_id');
    }

    public function asset()
    {
        return $this->belongsTo(FacilityAsset::class, 'asset_id');
    }
}
