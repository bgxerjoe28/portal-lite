<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MenusTableSeeder extends Seeder
{
    /**
     * Seed menus for portal-lite (CBT & Penugasan).
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $guru  = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        $siswa = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        // Bersihkan menu modul yang ditiadakan di portal-lite
        Menu::where('to', '/surat/dashboard')->orWhere('label', 'Modul Surat')->delete();

        // 1. Dashboard
        $dashAdmin = Menu::updateOrCreate(['to' => '/admin/dashboard'], [
            'label' => 'Dashboard Admin',
            'icon' => 'pi pi-home',
            'sort_order' => 1,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $dashAdmin->roles()->syncWithoutDetaching([$admin->id]);

        $dashGuru = Menu::updateOrCreate(['to' => '/teacher/dashboard'], [
            'label' => 'Dashboard Guru',
            'icon' => 'pi pi-home',
            'sort_order' => 2,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $dashGuru->roles()->syncWithoutDetaching([$guru->id]);

        // 2. Administrasi Utama (Admin)
        $adminGroup = Menu::updateOrCreate(['label' => 'Administrasi Utama', 'parent_id' => null], [
            'icon' => 'pi pi-briefcase',
            'sort_order' => 3,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $adminGroup->roles()->syncWithoutDetaching([$admin->id]);

        $adminSubmenus = [
            ['to' => '/admin/teachers', 'label' => 'Data Guru', 'icon' => 'pi pi-users', 'sort_order' => 1],
            ['to' => '/admin/students', 'label' => 'Data Siswa', 'icon' => 'pi pi-user', 'sort_order' => 2],
            ['to' => '/admin/classrooms', 'label' => 'Managemen Kelas', 'icon' => 'pi pi-th-large', 'sort_order' => 3],
            ['to' => '/admin/subjects', 'label' => 'Mata Pelajaran', 'icon' => 'pi pi-book', 'sort_order' => 4],
            ['to' => '/admin/schedules', 'label' => 'Jam Mengajar', 'icon' => 'pi pi-calendar', 'sort_order' => 5],
            ['to' => '/admin/academic-years', 'label' => 'Tahun Ajaran', 'icon' => 'pi pi-calendar-times', 'sort_order' => 6],
            ['to' => '/admin/calendar', 'label' => 'Kalender Akademik', 'icon' => 'pi pi-calendar', 'sort_order' => 7],
            ['to' => '/admin/users', 'label' => 'Managemen User', 'icon' => 'pi pi-users', 'sort_order' => 8],
            ['to' => '/admin/monitoring/schedule-class', 'label' => 'Jadwal Mengajar (Admin)', 'icon' => 'pi pi-table', 'sort_order' => 9],
            ['to' => '/admin/curriculum/dashboard', 'label' => 'Kur Dashboard', 'icon' => 'pi pi-box', 'sort_order' => 10],
            ['to' => '/admin/menus', 'label' => 'Menu Manager', 'icon' => 'pi pi-bars', 'sort_order' => 11],
        ];

        foreach ($adminSubmenus as $sub) {
            $m = Menu::updateOrCreate(['to' => $sub['to']], [
                'parent_id' => $adminGroup->id,
                'label' => $sub['label'],
                'icon' => $sub['icon'],
                'sort_order' => $sub['sort_order'],
                'is_separator' => false,
                'is_active' => true,
            ]);
            $m->roles()->syncWithoutDetaching([$admin->id]);
        }

        // 3. Kegiatan Belajar (Guru)
        $kegiatanGroup = Menu::updateOrCreate(['label' => 'Kegiatan Belajar', 'parent_id' => null], [
            'icon' => 'pi pi-calendar',
            'sort_order' => 4,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $kegiatanGroup->roles()->syncWithoutDetaching([$guru->id]);

        $guruSubmenus = [
            ['to' => '/teacher/my-schedules', 'label' => 'Jadwal Mengajar Saya', 'icon' => 'pi pi-table', 'sort_order' => 1],
            ['to' => '/teacher/curriculum/dashboard', 'label' => 'Beban Mengajar Saya', 'icon' => 'pi pi-chart-bar', 'sort_order' => 2],
            ['to' => '/calendar/view', 'label' => 'Kalender Akademik', 'icon' => 'pi pi-calendar', 'sort_order' => 3],
        ];

        foreach ($guruSubmenus as $sub) {
            $m = Menu::updateOrCreate(['to' => $sub['to']], [
                'parent_id' => $kegiatanGroup->id,
                'label' => $sub['label'],
                'icon' => $sub['icon'],
                'sort_order' => $sub['sort_order'],
                'is_separator' => false,
                'is_active' => true,
            ]);
            $m->roles()->syncWithoutDetaching([$guru->id]);
        }

        // 4. Modul Ujian CBT (Admin & Guru)
        $cbtGroup = Menu::updateOrCreate(['label' => 'Modul Ujian (CBT)'], [
            'icon' => 'pi pi-file-edit',
            'sort_order' => 5,
            'is_separator' => true,
            'is_active' => true,
        ]);
        $cbtGroup->roles()->syncWithoutDetaching([$admin->id, $guru->id]);

        $cbtSubmenus = [
            ['to' => '/cbt/bank', 'label' => 'Bank Soal', 'icon' => 'pi pi-server', 'sort_order' => 1],
            ['to' => '/cbt/exams', 'label' => 'Jadwal Ujian', 'icon' => 'pi pi-calendar', 'sort_order' => 2],
            ['to' => '/cbt/proctor', 'label' => 'Ruang & Pengawas', 'icon' => 'pi pi-users', 'sort_order' => 3],
            ['to' => '/cbt/sessions', 'label' => 'Sesi & Jadwal Pengawas', 'icon' => 'pi pi-clock', 'sort_order' => 4],
        ];

        foreach ($cbtSubmenus as $sub) {
            $m = Menu::updateOrCreate(['to' => $sub['to']], [
                'parent_id' => $cbtGroup->id,
                'label' => $sub['label'],
                'icon' => $sub['icon'],
                'sort_order' => $sub['sort_order'],
                'is_separator' => false,
                'is_active' => true,
            ]);
            $m->roles()->syncWithoutDetaching([$admin->id, $guru->id]);
        }

        // 5. Penugasan Mata Pelajaran (Guru)
        $penugasan = Menu::updateOrCreate(['to' => '/teacher/assignments'], [
            'label' => 'Penugasan Mata Pelajaran',
            'icon' => 'pi pi-file-edit',
            'sort_order' => 6,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $penugasan->roles()->syncWithoutDetaching([$guru->id]);

        // 6. Ujian CBT (Siswa)
        $studentCbt = Menu::updateOrCreate(['to' => '/student/cbt'], [
            'label' => 'Ujian CBT',
            'icon' => 'pi pi-file-edit',
            'sort_order' => 7,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $studentCbt->roles()->syncWithoutDetaching([$siswa->id]);

        // 7. Pengaturan (Admin)
        $settingGroup = Menu::updateOrCreate(['label' => 'Pengaturan', 'parent_id' => null], [
            'icon' => 'pi pi-cog',
            'sort_order' => 99,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $settingGroup->roles()->syncWithoutDetaching([$admin->id]);

        $settingChild = Menu::updateOrCreate(['to' => '/admin/settings'], [
            'parent_id' => $settingGroup->id,
            'label' => 'Pengaturan Situs',
            'icon' => 'pi pi-sliders-h',
            'sort_order' => 1,
            'is_separator' => false,
            'is_active' => true,
        ]);
        $settingChild->roles()->syncWithoutDetaching([$admin->id]);
    }
}