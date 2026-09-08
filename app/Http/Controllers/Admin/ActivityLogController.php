<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan halaman daftar log aktivitas & audit trail.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role', 'action', 'date_from', 'date_to', 'category', 'hide_student_logins']);

        // Default hide_student_logins to true if not explicitly specified
        if (!$request->has('hide_student_logins')) {
            $filters['hide_student_logins'] = 'true';
        }

        $logs = ActivityLog::filter($filters)
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Hitung statistik kategori cepat (mengikuti filter tanggal & search dasar)
        $baseCountQuery = ActivityLog::query();
        if (isset($filters['hide_student_logins']) && filter_var($filters['hide_student_logins'], FILTER_VALIDATE_BOOLEAN)) {
            $baseCountQuery->where(function ($sub) {
                $sub->whereNotIn('action', ['LOGIN', 'LOGOUT'])
                    ->orWhere('role', '!=', 'siswa')
                    ->orWhereNull('role');
            });
        }
        if (!empty($filters['date_from'])) {
            $baseCountQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseCountQuery->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $baseCountQuery->where(function ($sub) use ($s) {
                $sub->where('description', 'like', "%{$s}%")
                    ->orWhere('user_name', 'like', "%{$s}%")
                    ->orWhere('user_email', 'like', "%{$s}%")
                    ->orWhere('action', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'kesiswaan' => (clone $baseCountQuery)->where(function ($q) {
                $q->where('action', 'like', 'EXTRA_%')
                  ->orWhere('action', 'like', 'DISCIPLINE_%')
                  ->orWhere('action', 'like', 'KESISWAAN_%');
            })->count(),
            'cbt' => (clone $baseCountQuery)->where('action', 'like', 'CBT_%')->count(),
            'assignment' => (clone $baseCountQuery)->where(function ($q) {
                $q->where('action', 'like', 'ASSIGNMENT_%')
                  ->orWhere('action', 'like', 'PENUGASAN_%')
                  ->orWhere('action', 'like', 'GRADE_%');
            })->count(),
            'academic' => (clone $baseCountQuery)->where(function ($q) {
                $q->where('action', 'like', 'USER_%')
                  ->orWhere('action', 'like', 'SCHEDULE_%')
                  ->orWhere('action', 'like', 'CLASSROOM_%')
                  ->orWhere('action', 'like', 'SUBJECT_%')
                  ->orWhere('action', 'like', 'BACKUP%')
                  ->orWhere('action', 'like', 'RESTORE%')
                  ->orWhere('action', 'like', 'IMPERSONATE%');
            })->count(),
            'auth' => (clone $baseCountQuery)->whereIn('action', ['LOGIN', 'LOGOUT', 'LOGIN_FAILED'])->count(),
        ];

        // Ambil daftar unik role dan action untuk filter dropdown
        $roles = ActivityLog::whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->filter()
            ->values();

        $actions = ActivityLog::whereNotNull('action')
            ->distinct()
            ->pluck('action')
            ->filter()
            ->values();

        return Inertia::render('Admin/ActivityLog/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'counts' => $counts,
            'availableRoles' => $roles,
            'availableActions' => $actions,
        ]);
    }
}
