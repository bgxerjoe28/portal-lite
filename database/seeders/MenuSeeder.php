<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Role sudah ada
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $guru  = Role::firstOrCreate(['name' => 'guru']);
        $siswa = Role::firstOrCreate(['name' => 'siswa']);

        // 2. Dashboard Admin
        $m1 = Menu::create([
            'label' => 'Dashboard Admin',
            'icon'  => 'pi pi-home',
            'to'    => '/admin/dashboard',
            'sort_order' => 1
        ]);
        $m1->roles()->attach($admin);

        // 3. Dashboard Guru
        $m2 = Menu::create([
            'label' => 'Dashboard Guru',
            'icon'  => 'pi pi-home',
            'to'    => '/teacher/dashboard',
            'sort_order' => 2
        ]);
        $m2->roles()->attach($guru);

        // 4. Group: Administrasi (Hanya Admin)
        $m3 = Menu::create([
            'label' => 'Administrasi Utama',
            'icon'  => 'pi pi-briefcase',
            'sort_order' => 3
        ]);
        $m3->roles()->attach($admin);

        // Sub-menu Administrasi
        $sm1 = Menu::create([
            'parent_id' => $m3->id,
            'label' => 'Data Guru',
            'icon'  => 'pi pi-users',
            'to'    => '/admin/teachers',
            'sort_order' => 1
        ]);
        $sm1->roles()->attach($admin);

        $sm2 = Menu::create([
            'parent_id' => $m3->id,
            'label' => 'Data Siswa',
            'icon'  => 'pi pi-user',
            'to'    => '/admin/students',
            'sort_order' => 2
        ]);
        $sm2->roles()->attach($admin);

        // 5. Group: KBM (Admin & Guru)
        $m4 = Menu::create([
            'label' => 'Kegiatan Belajar',
            'icon'  => 'pi pi-book',
            'sort_order' => 4
        ]);
        $m4->roles()->attach([$admin->id, $guru->id]);

        Menu::create([
            'parent_id' => $m4->id,
            'label' => 'Jadwal Mengajar',
            'icon'  => 'pi pi-calendar',
            'to'    => '/academic/schedule',
            'sort_order' => 1
        ])->roles()->attach([$admin->id, $guru->id]);

        // 6. Menu Siswa
        $m5 = Menu::create([
            'label' => 'Materi & Tugas',
            'icon'  => 'pi pi-download',
            'to'    => '/student/assignments',
            'sort_order' => 5
        ]);
        $m5->roles()->attach($siswa);

        // 7. Guru BK
        $guruBk = Role::firstOrCreate(['name' => 'guru bk']);
        $mBk = Menu::create([
            'label' => 'Dashboard Guru BK',
            'icon'  => 'pi pi-home',
            'to'    => '/bk/dashboard',
            'sort_order' => 2
        ]);
        $mBk->roles()->attach($guruBk);

        $mPermit = Menu::firstOrCreate([
            'to' => '/permits'
        ], [
            'label' => 'Input Izin & Presensi',
            'icon' => 'pi pi-calendar-plus',
            'sort_order' => 4
        ]);
        $mPermit->roles()->attach([$guru->id, $guruBk->id]);

        // 8. Administrasi Surat
        $mSurat = Menu::create([
            'label' => 'Administrasi Surat',
            'icon'  => 'pi pi-envelope',
            'sort_order' => 6
        ]);
        $mSurat->roles()->attach($admin);

        Menu::create(['parent_id' => $mSurat->id, 'label' => 'Dashboard Surat', 'icon' => 'pi pi-chart-line', 'to' => '/surat/dashboard', 'sort_order' => 1])->roles()->attach($admin);
        Menu::create(['parent_id' => $mSurat->id, 'label' => 'Surat Masuk', 'icon' => 'pi pi-inbox', 'to' => '/surat/masuk', 'sort_order' => 2])->roles()->attach($admin);
        Menu::create(['parent_id' => $mSurat->id, 'label' => 'Surat Keluar', 'icon' => 'pi pi-send', 'to' => '/surat/keluar', 'sort_order' => 3])->roles()->attach($admin);
        Menu::create(['parent_id' => $mSurat->id, 'label' => 'Kalender Surat', 'icon' => 'pi pi-calendar', 'to' => '/surat/kalender', 'sort_order' => 4])->roles()->attach($admin);
        Menu::create(['parent_id' => $mSurat->id, 'label' => 'Master Jenis Surat', 'icon' => 'pi pi-tags', 'to' => '/surat/master/jenis', 'sort_order' => 5])->roles()->attach($admin);
        Menu::create(['parent_id' => $mSurat->id, 'label' => 'Master Kelompok', 'icon' => 'pi pi-users', 'to' => '/surat/master/kelompok', 'sort_order' => 6])->roles()->attach($admin);
    }
}