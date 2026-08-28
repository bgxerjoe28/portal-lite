<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Setting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return $default;
            }
            $setting = self::where('key', $key)->first();
            
            if ($setting && $setting->value !== null) {
                return $setting->value;
            }
        } catch (\Throwable $e) {
            return $default;
        }

        return $default;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public static function set(string $key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
