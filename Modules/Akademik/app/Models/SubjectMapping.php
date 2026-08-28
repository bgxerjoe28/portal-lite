<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectMapping extends Model
{
    use SoftDeletes;

    protected $fillable = ['subject_id', 'level', 'subject_group_id'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function group()
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }
}
