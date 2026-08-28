<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cari role admin
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            return;
        }

        // Buat Parent Menu 'Pengaturan'
        $parentMenu = Menu::create([
            'label' => 'Pengaturan',
            'icon' => 'pi pi-cog',
            'sort_order' => 100,
            'is_separator' => false,
            'is_active' => true
        ]);
        $parentMenu->roles()->attach($adminRole);

        // Buat Child Menu 'Pengaturan Situs'
        $childMenu = Menu::create([
            'parent_id' => $parentMenu->id,
            'label' => 'Pengaturan Situs',
            'icon' => 'pi pi-desktop',
            'to' => '/admin/settings',
            'sort_order' => 1,
            'is_separator' => false,
            'is_active' => true
        ]);
        $childMenu->roles()->attach($adminRole);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $parentMenu = Menu::where('label', 'Pengaturan')->whereNull('parent_id')->first();
        if ($parentMenu) {
            $parentMenu->delete(); // Cascade delete akan menghapus child menu juga
        }
    }
};
