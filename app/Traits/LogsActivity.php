<?php

namespace App\Traits;

use App\Services\ActivityLogger;
use Illuminate\Support\Str;

trait LogsActivity
{
    /**
     * Boot trait LogsActivity pada Eloquent Model.
     */
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            if (static::shouldLogActivity('created')) {
                $description = method_exists($model, 'getActivityDescription')
                    ? $model->getActivityDescription('created')
                    : 'Membuat data ' . static::getActivityModelName();

                ActivityLogger::log(
                    'CREATED',
                    $description,
                    $model,
                    null,
                    static::filterLogAttributes($model->getAttributes())
                );
            }
        });

        static::updated(function ($model) {
            if (static::shouldLogActivity('updated')) {
                $changes = $model->getChanges();
                
                // Jangan log jika yang berubah hanya timestamps atau data login
                unset($changes['updated_at'], $changes['last_login_at'], $changes['last_login_ip']);
                if (empty($changes)) {
                    return;
                }

                $original = [];
                foreach (array_keys($changes) as $key) {
                    $original[$key] = $model->getOriginal($key);
                }

                $description = method_exists($model, 'getActivityDescription')
                    ? $model->getActivityDescription('updated')
                    : 'Memperbarui data ' . static::getActivityModelName();

                ActivityLogger::log(
                    'UPDATED',
                    $description,
                    $model,
                    static::filterLogAttributes($original),
                    static::filterLogAttributes($changes)
                );
            }
        });

        static::deleted(function ($model) {
            if (static::shouldLogActivity('deleted')) {
                $description = method_exists($model, 'getActivityDescription')
                    ? $model->getActivityDescription('deleted')
                    : 'Menghapus data ' . static::getActivityModelName();

                ActivityLogger::log(
                    'DELETED',
                    $description,
                    $model,
                    static::filterLogAttributes($model->getAttributes()),
                    null
                );
            }
        });
    }

    /**
     * Apakah event ini harus dilog.
     */
    protected static function shouldLogActivity(string $event): bool
    {
        return true;
    }

    /**
     * Dapatkan nama model yang mudah dibaca.
     */
    protected static function getActivityModelName(): string
    {
        return Str::headline(class_basename(static::class));
    }

    /**
     * Sembunyikan atribut sensitif (seperti password) dari log.
     */
    protected static function filterLogAttributes(array $attributes): array
    {
        $hidden = ['password', 'remember_token'];

        foreach ($hidden as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = '*** HIDDEN ***';
            }
        }

        return $attributes;
    }
}
