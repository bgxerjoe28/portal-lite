<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat Permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-sarpras',
            'approve-sarpras-stage1',
            'borrow-facility',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Berikan manage-sarpras ke Admin
        $roleAdmin = Role::where('name', 'admin')->first();
        if ($roleAdmin) {
            $roleAdmin->givePermissionTo(Permission::all());
        }

        $roleGuru = Role::where('name', 'guru')->first();
        if ($roleGuru) {
            $roleGuru->givePermissionTo('approve-sarpras-stage1');
        }

        $roleSiswa = Role::where('name', 'siswa')->first();
        if ($roleSiswa) {
            $roleSiswa->givePermissionTo('borrow-facility');
        }

        // 2. Tambah Menu Navigasi
        // Parent Admin Sarpras
        $adminSarprasParentId = DB::table('menus')->insertGetId([
            'parent_id' => null,
            'label' => 'Sarana & Prasarana',
            'icon' => 'pi pi-building',
            'to' => null,
            'sort_order' => 45,
            'is_separator' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($roleAdmin) {
            DB::table('menu_role')->insert([
                'menu_id' => $adminSarprasParentId,
                'role_id' => $roleAdmin->id,
            ]);
        }

        $adminSubmenus = [
            ['label' => 'Daftar Ruangan', 'icon' => 'pi pi-home', 'to' => '/admin/sarpras/rooms', 'sort_order' => 1],
            ['label' => 'Aset & Tag QR', 'icon' => 'pi pi-box', 'to' => '/admin/sarpras/assets', 'sort_order' => 2],
            ['label' => 'Peminjaman & Approval', 'icon' => 'pi pi-calendar-plus', 'to' => '/admin/sarpras/reservations', 'sort_order' => 3],
            ['label' => 'Kalender Jadwal', 'icon' => 'pi pi-calendar', 'to' => '/admin/sarpras/reservations/calendar', 'sort_order' => 4],
            ['label' => 'Scanner QR Serah Terima', 'icon' => 'pi pi-qrcode', 'to' => '/admin/sarpras/scanner', 'sort_order' => 5],
            ['label' => 'Berita Acara Kerusakan', 'icon' => 'pi pi-exclamation-triangle', 'to' => '/admin/sarpras/damages', 'sort_order' => 6],
            ['label' => 'Jadwal Khusus (Blackout)', 'icon' => 'pi pi-lock', 'to' => '/admin/sarpras/blackouts', 'sort_order' => 7],
        ];

        foreach ($adminSubmenus as $sub) {
            $subId = DB::table('menus')->insertGetId([
                'parent_id' => $adminSarprasParentId,
                'label' => $sub['label'],
                'icon' => $sub['icon'],
                'to' => $sub['to'],
                'sort_order' => $sub['sort_order'],
                'is_separator' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($roleAdmin) {
                DB::table('menu_role')->insert([
                    'menu_id' => $subId,
                    'role_id' => $roleAdmin->id,
                ]);
            }
        }

        // Menu Guru (Persetujuan Sarpras Eskul)
        if ($roleGuru) {
            $guruMenuId = DB::table('menus')->insertGetId([
                'parent_id' => null,
                'label' => 'Persetujuan Sarpras Eskul',
                'icon' => 'pi pi-check-square',
                'to' => '/teacher/sarpras-approvals',
                'sort_order' => 60,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('menu_role')->insert([
                'menu_id' => $guruMenuId,
                'role_id' => $roleGuru->id,
            ]);
        }

        // Menu Siswa (Peminjaman Ruang & Alat)
        if ($roleSiswa) {
            $siswaMenuId = DB::table('menus')->insertGetId([
                'parent_id' => null,
                'label' => 'Peminjaman Ruang & Alat',
                'icon' => 'pi pi-calendar-plus',
                'to' => '/student/sarpras-reservations',
                'sort_order' => 50,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('menu_role')->insert([
                'menu_id' => $siswaMenuId,
                'role_id' => $roleSiswa->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $sarprasMenuIds = DB::table('menus')
            ->where('label', 'like', '%Sarpras%')
            ->orWhere('to', 'like', '%/sarpras%')
            ->pluck('id');

        DB::table('menu_role')->whereIn('menu_id', $sarprasMenuIds)->delete();
        DB::table('menus')->whereIn('id', $sarprasMenuIds)->delete();
    }
};
