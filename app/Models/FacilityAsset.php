<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FacilityAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'brand_model',
        'serial_number',
        'qr_code_token',
        'condition',
        'status',
        'location',
        'image',
        'images',
        'notes',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($asset) {
            if (empty($asset->qr_code_token)) {
                $asset->qr_code_token = 'AST-' . strtoupper(Str::random(10));
            }
        });
    }

    public function reservationAssets()
    {
        return $this->hasMany(FacilityReservationAsset::class, 'asset_id');
    }

    public function damageReports()
    {
        return $this->hasMany(FacilityDamageReport::class, 'asset_id');
    }
}
