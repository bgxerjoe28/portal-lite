<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $admin = Role::where('name', 'admin')->first();
        $guru = Role::where('name', 'guru')->first();

        // Cari group menu "Modul Ujian (CBT)"
        $groupMenu = Menu::where('label', 'Modul Ujian (CBT)')->first();

        if ($groupMenu) {
            $proctorMenu = Menu::create([
                'parent_id' => $groupMenu->id,
                'label' => 'Pengawas Ujian',
                'icon' => 'pi pi-shield',
                'to' => '/cbt/proctor',
                'sort_order' => 3,
            ]);

            if ($admin) $proctorMenu->roles()->attach($admin->id);
            if ($guru) $proctorMenu->roles()->attach($guru->id);
        }
    }

    public function down(): void
    {
        $menu = Menu::where('to', '/cbt/proctor')->first();
        if ($menu) {
            $menu->roles()->detach();
            $menu->delete();
        }
    }
};
