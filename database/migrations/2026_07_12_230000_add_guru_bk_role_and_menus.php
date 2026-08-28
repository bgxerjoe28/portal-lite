<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\Menu;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Flush spatie cache before creating
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Role
        $guruBk = Role::firstOrCreate(['name' => 'guru bk', 'guard_name' => 'web']);

        // 2. Create Dashboard Menu
        $dashboardMenu = Menu::updateOrCreate(
            ['to' => '/bk/dashboard'],
            [
                'label' => 'Dashboard Guru BK',
                'icon' => 'pi pi-home',
                'sort_order' => 2
            ]
        );
        // Attach role if not already attached
        if (!$dashboardMenu->roles->contains($guruBk->id)) {
            $dashboardMenu->roles()->attach($guruBk);
        }

        // 3. Create Izin Menu
        $izinMenu = Menu::updateOrCreate(
            ['to' => '/permits'],
            [
                'label' => 'Input Izin & Presensi',
                'icon' => 'pi pi-calendar-plus',
                'sort_order' => 4
            ]
        );
        // Make sure guru bk has this menu
        if (!$izinMenu->roles->contains($guruBk->id)) {
            $izinMenu->roles()->attach($guruBk);
        }
        
        // Also make sure guru has this menu if it's new
        $guru = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        if (!$izinMenu->roles->contains($guru->id)) {
            $izinMenu->roles()->attach($guru);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove menu assignments and role
        $guruBk = Role::where('name', 'guru bk')->first();
        if ($guruBk) {
            $dashboardMenu = Menu::where('to', '/bk/dashboard')->first();
            if ($dashboardMenu) {
                $dashboardMenu->roles()->detach($guruBk);
            }
            
            $izinMenu = Menu::where('to', '/permits')->first();
            if ($izinMenu) {
                $izinMenu->roles()->detach($guruBk);
            }

            $guruBk->delete();
        }
    }
};
