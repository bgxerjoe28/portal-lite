<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityReportItem extends Model
{
    protected $fillable = [
        'facility_report_id',
        'type',
        'item_name',
        'room_name',
        'severity',
        'recommendation',
        'status',
        'notes',
    ];

    public function facilityReport()
    {
        return $this->belongsTo(FacilityReport::class);
    }
}
