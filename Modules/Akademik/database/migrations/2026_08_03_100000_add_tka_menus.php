<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $admin = Role::where('name', 'admin')->first();
        $siswa = Role::where('name', 'siswa')->first();

        // 1. MENU ADMIN
        if ($admin) {
            $parentAdmin = Menu::where('label', 'Ref Kurikulum')->whereNull('parent_id')->first();
            if (!$parentAdmin) {
                $parentAdmin = Menu::where('label', 'Kegiatan Belajar')->whereNull('parent_id')->first();
            }

            $tkaAdmin = Menu::firstOrCreate(
                ['to' => '/akademik/tka-subjects'],
                [
                    'parent_id' => $parentAdmin ? $parentAdmin->id : null,
                    'label' => 'Manajemen TKA',
                    'icon' => 'pi pi-bookmark',
                    'sort_order' => 25,
                    'is_active' => true,
                ]
            );
            $tkaAdmin->roles()->syncWithoutDetaching([$admin->id]);
        }

        // 2. MENU SISWA (Pilihan TKA)
        if ($siswa) {
            $tkaSiswa = Menu::firstOrCreate(
                ['to' => '/student/tka'],
                [
                    'label' => 'Pilihan TKA',
                    'icon' => 'pi pi-book',
                    'sort_order' => 7,
                    'is_active' => true,
                ]
            );
            $tkaSiswa->roles()->syncWithoutDetaching([$siswa->id]);
        }
    }

    public function down(): void
    {
        Menu::where('to', '/akademik/tka-subjects')->delete();
        Menu::where('to', '/student/tka')->delete();
    }
};
