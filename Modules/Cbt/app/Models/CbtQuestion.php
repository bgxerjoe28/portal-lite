<?php

namespace Modules\Cbt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cbt\Casts\JsonUnescapedUnicode;

class CbtQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'cbt_bank_id',
        'question_type',
        'question_text',
        'options',
        'correct_answer',
        'score',
        'lock_n',     // true = tidak ikut diacak saat shuffle
        'grouping',   // nomor grup: soal dalam grup sama tetap berurutan saat shuffle
    ];

    protected $casts = [
        'options'        => JsonUnescapedUnicode::class,
        'correct_answer' => JsonUnescapedUnicode::class,
        'score'          => 'float',
        'lock_n'         => 'boolean',
    ];

    public function bank()
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }
}
