<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\MassPrunable;

class ActivityLog extends Model
{
    use MassPrunable;

    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope untuk memfilter log aktivitas pada halaman Admin.
     */
    public function scopeFilter($query, array $filters)
    {
        // Filter kategori aksi
        $query->when($filters['category'] ?? null, function ($q, $category) {
            if ($category === 'cbt') {
                $q->where('action', 'like', 'CBT_%');
            } elseif ($category === 'kesiswaan') {
                $q->where(function ($sub) {
                    $sub->where('action', 'like', 'EXTRA_%')
                        ->orWhere('action', 'like', 'DISCIPLINE_%')
                        ->orWhere('action', 'like', 'KESISWAAN_%');
                });
            } elseif ($category === 'assignment') {
                $q->where(function ($sub) {
                    $sub->where('action', 'like', 'ASSIGNMENT_%')
                        ->orWhere('action', 'like', 'PENUGASAN_%')
                        ->orWhere('action', 'like', 'GRADE_%');
                });
            } elseif ($category === 'academic') {
                $q->where(function ($sub) {
                    $sub->where('action', 'like', 'USER_%')
                        ->orWhere('action', 'like', 'SCHEDULE_%')
                        ->orWhere('action', 'like', 'CLASSROOM_%')
                        ->orWhere('action', 'like', 'SUBJECT_%')
                        ->orWhere('action', 'like', 'BACKUP%')
                        ->orWhere('action', 'like', 'RESTORE%')
                        ->orWhere('action', 'like', 'IMPERSONATE%');
                });
            } elseif ($category === 'auth') {
                $q->whereIn('action', ['LOGIN', 'LOGOUT', 'LOGIN_FAILED']);
            }
        });

        // Sembunyikan login/logout siswa jika diaktifkan (default agar log administratif tidak tenggelam)
        $query->when(isset($filters['hide_student_logins']) && filter_var($filters['hide_student_logins'], FILTER_VALIDATE_BOOLEAN), function ($q) {
            $q->where(function ($sub) {
                $sub->whereNotIn('action', ['LOGIN', 'LOGOUT'])
                    ->orWhere('role', '!=', 'siswa')
                    ->orWhereNull('role');
            });
        });

        // Cari dari deskripsi, nama user, atau email
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        });

        // Filter role
        $query->when($filters['role'] ?? null, function ($q, $role) {
            $q->where('role', $role);
        });

        // Filter aksi
        $query->when($filters['action'] ?? null, function ($q, $action) {
            $q->where('action', $action);
        });

        // Filter tanggal dari
        $query->when($filters['date_from'] ?? null, function ($q, $dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        });

        // Filter tanggal sampai
        $query->when($filters['date_to'] ?? null, function ($q, $dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        });
    }

    /**
     * Tentukan query untuk menghapus model yang sudah usang (pruning).
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}
