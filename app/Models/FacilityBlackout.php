<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityBlackout extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'name',
        'reason',
        'start_time',
        'end_time',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(FacilityRoom::class, 'room_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
