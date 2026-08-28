<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    protected $fillable = ['name'];
    // Kita matikan timestamps jika tidak butuh, tapi default ada gapapa
}
