<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Role kepala sekolah jika belum ada
        $role = Role::firstOrCreate(['name' => 'kepala sekolah', 'guard_name' => 'web']);

        // Reset PostgreSQL sequence to prevent duplicate key key errors
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('menus', 'id'), COALESCE((SELECT MAX(id) FROM menus), 0) + 1, false);");
        }

        // 2. Buat Menu Dashboard Kepala Sekolah jika belum ada
        $menu = Menu::firstOrCreate(
            ['to' => '/ks/dashboard'],
            [
                'label' => 'Dashboard Kepala Sekolah',
                'icon' => 'pi pi-chart-bar',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        // 3. Hubungkan Menu dengan Role (tabel pivot menu_role)
        if (!$menu->roles()->where('role_id', $role->id)->exists()) {
            $menu->roles()->attach($role);
        }
    }

    public function down(): void
    {
        // Cari menu dan detach
        $menu = Menu::where('to', '/ks/dashboard')->first();
        if ($menu) {
            $menu->roles()->detach();
            $menu->delete();
        }
    }
};
