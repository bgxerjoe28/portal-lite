<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityReportUpdate extends Model
{
    protected $fillable = [
        'facility_report_id',
        'updated_by',
        'new_status',
        'notes',
        'is_read_by_reporter'
    ];

    public $timestamps = false;

    public function report()
    {
        return $this->belongsTo(FacilityReport::class, 'facility_report_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
