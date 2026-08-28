<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaAttachment extends Model
{
    protected $fillable = [
        'teaching_agenda_id',
        'type',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
    ];

    public function agenda()
    {
        return $this->belongsTo(TeachingAgenda::class, 'teaching_agenda_id');
    }
}
