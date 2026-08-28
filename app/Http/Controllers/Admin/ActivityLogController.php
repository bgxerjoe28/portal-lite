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
        $filters = $request->only(['search', 'role', 'action', 'date_from', 'date_to']);

        $logs = ActivityLog::filter($filters)
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

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
            'availableRoles' => $roles,
            'availableActions' => $actions,
        ]);
    }
}
