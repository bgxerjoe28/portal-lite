<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Akademik\Models\Teacher;
use Modules\Cbt\Casts\JsonUnescapedUnicode;

class CbtProctorSchedule extends Model
{
    protected $table = 'cbt_proctor_schedules';
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'token_generated_at' => 'datetime',
        'present_students' => JsonUnescapedUnicode::class,
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(CbtRoom::class, 'cbt_room_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CbtSession::class, 'cbt_session_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
