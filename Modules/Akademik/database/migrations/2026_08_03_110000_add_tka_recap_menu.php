<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $admin = Role::where('name', 'admin')->first();
        if (!$admin) return;

        $parentAdmin = Menu::where('label', 'Ref Kurikulum')->whereNull('parent_id')->first();
        if (!$parentAdmin) {
            $parentAdmin = Menu::where('label', 'Kegiatan Belajar')->whereNull('parent_id')->first();
        }

        $tkaRecapMenu = Menu::firstOrCreate(
            ['to' => '/akademik/tka-recap'],
            [
                'parent_id' => $parentAdmin ? $parentAdmin->id : null,
                'label' => 'Rekap Siswa TKA',
                'icon' => 'pi pi-list',
                'sort_order' => 26,
                'is_active' => true,
            ]
        );
        $tkaRecapMenu->roles()->syncWithoutDetaching([$admin->id]);
    }

    public function down(): void
    {
        Menu::where('to', '/akademik/tka-recap')->delete();
    }
};
