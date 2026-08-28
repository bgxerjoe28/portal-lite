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
