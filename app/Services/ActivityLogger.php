<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Catat log aktivitas ke dalam tabel activity_logs.
     *
     * @param string $action Contoh: LOGIN, CREATED, UPDATED, DELETED, LOGIN_FAILED
     * @param string $description Deskripsi aktivitas manusiawi
     * @param Model|null $subject Model/Object yang diubah
     * @param array|null $oldValues Data sebelum diubah
     * @param array|null $newValues Data sesudah diubah
     * @param User|null $user User pelaku (default: current logged in user)
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): void {
        try {
            $user = $user ?? Auth::user();

            $role = null;
            $userName = 'System / Guest';
            $userEmail = null;
            $userId = null;

            if ($user) {
                $userId = $user->id;
                $userName = $user->name;
                $userEmail = $user->email;
                $role = $user->getRoleNames()->first() ?? 'user';
            }

            $logData = [
                'user_id' => $userId,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'role' => $role,
                'action' => strtoupper($action),
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject ? $subject->getKey() : null,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
                'created_at' => now(),
            ];

            \App\Jobs\ProcessActivityLogJob::dispatch($logData);

        } catch (\Exception $e) {
            // Jangan biarkan kegagalan logging menghentikan jalannya aplikasi
            Log::error('Gagal mencatat ActivityLog: ' . $e->getMessage());
        }
    }
}
