<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Akademik\Models\Classroom;

class FacilityReport extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'item_name',
        'location',
        'classroom_id',
        'latitude',
        'longitude',
        'severity',
        'status',
        'reported_by',
        'photos'
    ];

    protected $casts = [
        'photos' => 'array',
        'latitude' => 'double',
        'longitude' => 'double',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function updates()
    {
        return $this->hasMany(FacilityReportUpdate::class)->orderBy('created_at', 'asc');
    }

    public function items()
    {
        return $this->hasMany(FacilityReportItem::class);
    }
}
