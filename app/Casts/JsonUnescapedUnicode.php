<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom cast that encodes JSON with JSON_UNESCAPED_UNICODE.
 *
 * Laravel's built-in 'array' cast uses json_encode() without this flag,
 * producing \uXXXX escape sequences (e.g. \u2013 for en-dash).
 * PostgreSQL running in SQL_ASCII encoding rejects those sequences.
 * This cast stores actual UTF-8 characters instead of escape sequences.
 */
class JsonUnescapedUnicode implements CastsAttributes
{
    /**
     * Decode JSON from the database into a PHP array.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Encode a PHP array to JSON using JSON_UNESCAPED_UNICODE before storing.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
