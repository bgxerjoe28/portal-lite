<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtSession extends Model
{
    protected $table = 'cbt_sessions';
    protected $guarded = [];


    public function proctorSchedules(): HasMany
    {
        return $this->hasMany(CbtProctorSchedule::class, 'cbt_session_id');
    }
}
