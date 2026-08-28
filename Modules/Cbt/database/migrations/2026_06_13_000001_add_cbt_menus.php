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
        // Pastikan role sudah ada
        $admin = Role::where('name', 'admin')->first();
        $guru = Role::where('name', 'guru')->first();
        $siswa = Role::where('name', 'siswa')->first();

        // 1. Group / Separator CBT (Admin & Guru)
        $groupMenu = Menu::create([
            'label' => 'Modul Ujian (CBT)',
            'icon' => 'pi pi-file-edit',
            'sort_order' => 8,
            'is_separator' => true,
        ]);
        if ($admin) $groupMenu->roles()->attach($admin->id);
        if ($guru) $groupMenu->roles()->attach($guru->id);

        // 2. Sub-menu: Bank Soal (Admin & Guru)
        $bankMenu = Menu::create([
            'parent_id' => $groupMenu->id,
            'label' => 'Bank Soal',
            'icon' => 'pi pi-server',
            'to' => '/cbt/bank',
            'sort_order' => 1,
        ]);
        if ($admin) $bankMenu->roles()->attach($admin->id);
        if ($guru) $bankMenu->roles()->attach($guru->id);

        // 3. Sub-menu: Jadwal Ujian (Admin & Guru)
        $examMenu = Menu::create([
            'parent_id' => $groupMenu->id,
            'label' => 'Jadwal Ujian',
            'icon' => 'pi pi-calendar',
            'to' => '/cbt/exams',
            'sort_order' => 2,
        ]);
        if ($admin) $examMenu->roles()->attach($admin->id);
        if ($guru) $examMenu->roles()->attach($guru->id);

        // 4. Menu: Ujian CBT (Siswa)
        $studentCbtMenu = Menu::create([
            'label' => 'Ujian CBT',
            'icon' => 'pi pi-file-edit',
            'to' => '/student/cbt',
            'sort_order' => 9,
        ]);
        if ($siswa) $studentCbtMenu->roles()->attach($siswa->id);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus menu CBT
        $menus = Menu::whereIn('to', ['/cbt/bank', '/cbt/exams', '/student/cbt'])
            ->orWhere('label', 'Modul Ujian (CBT)')
            ->get();

        foreach ($menus as $menu) {
            $menu->roles()->detach();
            $menu->delete();
        }
    }
};
