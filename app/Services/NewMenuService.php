<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

class NewMenuService
{
    /**
     * Permission → URL pattern mapping.
     * Menu item yang URL-nya mengandung pattern akan disembunyikan
     * jika user tidak memiliki permission yang sesuai.
     */
    private static array $permissionMenuMap = [
        'manage-discipline'        => ['discipline'],
        'manage-permits'           => ['permits'],
        'manage-lates'             => ['lates'],
        'manage-facility-reports'  => ['facility-reports', 'admin/facility'],
    ];

    /**
     * Menu ID and URL patterns excluded in portal-lite
     */
    private static array $excludedMenuIds = [
        6, 24, 27, 30, 35, 41, 52, 53, 59, 60, 62, 66, 74, 75, 76
    ];

    private static array $excludedUrlPatterns = [
        'kesiswaan',
        'presensi',
        'attendance',
        'counseling',
        'discipline',
        'sarpras',
        'tka',
        'spmb',
        'daftar-ulang',
        'surat',
        'penilaian',
        'rapor',
        'extracurricular',
    ];

    /**
     * Role yang otomatis mendapat akses penuh ke semua modul (bypass filter).
     */
    private static array $privilegedRoles = ['admin', 'staff', 'pegawai'];

    /**
     * Mengambil menu dari DB dan memformatnya untuk PrimeVue
     */
    public static function getDynamicMenu(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        // Ambil nama-nama role user (Spatie)
        $roleNames = $user->getRoleNames(); // Mengembalikan koleksi nama role

        // Ambil menu tingkat atas (parent_id null) yang role-nya sesuai
        $menus = Menu::with(['children' => function($query) use ($roleNames) {
                        $query->whereHas('roles', function($q) use ($roleNames) {
                            $q->whereIn('name', $roleNames)
                            ->where('is_active', true);
                        })->where('is_active', true)->orderBy('sort_order');
                    }])
                    ->whereNull('parent_id')
                    ->whereNotIn('id', self::$excludedMenuIds)
                    ->whereHas('roles', function($q) use ($roleNames) {
                        $q->whereIn('name', $roleNames);                        
                    })
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

        $formatted = self::formatMenu($menus, $roleNames);

        if ($user->hasRole('siswa')) {
            $hasDashboard = collect($formatted)->contains(fn($i) => ($i['to'] ?? '') === '/student/dashboard');
            if (!$hasDashboard) {
                array_unshift($formatted, [
                    'label' => 'Dashboard Siswa',
                    'icon' => 'pi pi-home',
                    'to' => '/student/dashboard',
                    'separator' => false,
                ]);
            }
            $hasAssignment = collect($formatted)->contains(fn($i) => str_contains($i['to'] ?? '', 'assignment'));
            if (!$hasAssignment) {
                $formatted[] = [
                    'label' => 'Penugasan Mata Pelajaran',
                    'icon' => 'pi pi-file-edit',
                    'to' => '/student/assignments',
                    'separator' => false,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Fungsi rekursif untuk memformat struktur menu
     */
    private static function formatMenu($menus, $roleNames)
    {
        $result = [];
        $user = Auth::user();

        // Cek apakah user adalah role istimewa (bypass semua filter permission)
        $isPrivileged = $user && $user->hasAnyRole(self::$privilegedRoles);

        // Bangun daftar permission yang dimiliki user (untuk lookup cepat)
        $userPermissions = $user ? $user->getAllPermissions()->pluck('name')->toArray() : [];

        foreach ($menus as $menu) {
            // Exclude unwanted menu items in portal-lite
            if (in_array($menu->id, self::$excludedMenuIds)) {
                continue;
            }
            if ($menu->to) {
                foreach (self::$excludedUrlPatterns as $pattern) {
                    if (str_contains($menu->to, $pattern)) {
                        continue 2;
                    }
                }
            }
            // Filter menu berdasarkan mapping permission → url pattern
            if (!$isPrivileged && $menu->to) {
                $shouldHide = false;
                foreach (self::$permissionMenuMap as $permission => $urlPatterns) {
                    foreach ($urlPatterns as $pattern) {
                        if (str_contains($menu->to, $pattern)) {
                            // URL ini diproteksi oleh permission tersebut
                            if (!in_array($permission, $userPermissions)) {
                                $shouldHide = true;
                                break 2;
                            }
                        }
                    }
                }
                if ($shouldHide) continue;
            }

            $item = [
                'label'     => $menu->label,
                'icon'      => $menu->icon,
                'to'        => $menu->to, // PrimeVue RouterLink
                'separator' => (bool)$menu->is_separator,
            ];

            // Jika ada children, format secara rekursif
            if ($menu->children && $menu->children->count() > 0) {
                $children = self::formatMenu($menu->children, $roleNames);
                if (!empty($children)) {
                    $item['items'] = $children; // PrimeVue menggunakan key 'items' untuk submenu
                }
            }

            $result[] = $item;
        }

        return $result;
    }
}