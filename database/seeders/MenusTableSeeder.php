<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('menus')->delete();
        
        DB::table('menus')->insert(array (
            0 => 
            array (
                'id' => 1,
                'parent_id' => NULL,
                'label' => 'Dashboard Admin',
                'icon' => 'pi pi-home',
                'to' => '/admin/dashboard',
                'sort_order' => 1,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 10:02:52',
            ),
            1 => 
            array (
                'id' => 2,
                'parent_id' => NULL,
                'label' => 'Dashboard Guru',
                'icon' => 'pi pi-home',
                'to' => '/teacher/dashboard',
                'sort_order' => 2,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 10:02:52',
            ),
            2 => 
            array (
                'id' => 3,
                'parent_id' => NULL,
                'label' => 'Administrasi Utama',
                'icon' => 'pi pi-briefcase',
                'to' => NULL,
                'sort_order' => 3,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 10:02:52',
            ),
            3 => 
            array (
                'id' => 4,
                'parent_id' => 3,
                'label' => 'Data Guru',
                'icon' => 'pi pi-users',
                'to' => '/admin/teachers',
                'sort_order' => 1,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 10:02:52',
            ),
            4 => 
            array (
                'id' => 5,
                'parent_id' => 3,
                'label' => 'Data Siswa',
                'icon' => 'pi pi-user',
                'to' => '/admin/students',
                'sort_order' => 2,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 10:02:52',
            ),
            5 => 
            array (
                'id' => 10,
                'parent_id' => 3,
                'label' => 'Tahun Ajaran',
                'icon' => 'pi pi-calendar',
                'to' => '/admin/academic-years',
                'sort_order' => 0,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:55:49',
                'updated_at' => '2025-12-26 10:55:49',
            ),
            6 => 
            array (
                'id' => 11,
                'parent_id' => 9,
                'label' => 'Menu Manager',
                'icon' => 'pi pi-bullseye',
                'to' => '/admin/menus',
                'sort_order' => 0,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:58:44',
                'updated_at' => '2025-12-26 12:32:12',
            ),
            7 => 
            array (
                'id' => 9,
                'parent_id' => NULL,
                'label' => 'Administrasi Situs',
                'icon' => 'pi pi-globe',
                'to' => NULL,
                'sort_order' => 6,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:42:56',
                'updated_at' => '2025-12-26 12:41:00',
            ),
            8 => 
            array (
                'id' => 13,
                'parent_id' => 9,
                'label' => 'User Manager',
                'icon' => 'pi pi-users',
                'to' => '/admin/users',
                'sort_order' => 2,
                'is_separator' => false,
                'is_active' => false,
                'created_at' => '2025-12-26 12:48:14',
                'updated_at' => '2025-12-26 12:48:41',
            ),
            9 => 
            array (
                'id' => 14,
                'parent_id' => 3,
                'label' => 'Managemen Kelas',
                'icon' => 'pi pi-sitemap',
                'to' => '/admin/classrooms',
                'sort_order' => 3,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 12:53:54',
                'updated_at' => '2025-12-26 12:53:54',
            ),
            10 => 
            array (
                'id' => 15,
                'parent_id' => NULL,
                'label' => 'Ref Kurikulum',
                'icon' => 'pi pi-bookmark',
                'to' => NULL,
                'sort_order' => 4,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 12:55:56',
                'updated_at' => '2025-12-26 12:56:32',
            ),
            11 => 
            array (
                'id' => 6,
                'parent_id' => NULL,
                'label' => 'Kegiatan Belajar',
                'icon' => 'pi pi-book',
                'to' => NULL,
                'sort_order' => 5,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 12:56:51',
            ),
            12 => 
            array (
                'id' => 16,
                'parent_id' => 15,
                'label' => 'Mata Pelajaran',
                'icon' => 'pi pi-verified',
                'to' => '/admin/subjects',
                'sort_order' => 0,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 12:57:34',
                'updated_at' => '2025-12-26 12:57:34',
            ),
            13 => 
            array (
                'id' => 17,
                'parent_id' => 15,
                'label' => 'Jam Mengajar',
                'icon' => 'pi pi-chart-bar',
                'to' => '/admin/schedules',
                'sort_order' => 1,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 12:58:11',
                'updated_at' => '2025-12-26 12:58:11',
            ),
            14 => 
            array (
                'id' => 18,
                'parent_id' => 15,
                'label' => 'Kalender Akademik',
                'icon' => 'pi pi-calendar',
                'to' => '/admin/calendar',
                'sort_order' => 2,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 12:58:51',
                'updated_at' => '2025-12-26 12:58:51',
            ),
            15 => 
            array (
                'id' => 7,
                'parent_id' => 6,
                'label' => 'Jadwal Mengajar Saya',
                'icon' => 'pi pi-table',
                'to' => '/teacher/my-schedules',
                'sort_order' => 1,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 10:02:52',
                'updated_at' => '2025-12-26 12:59:54',
            ),
            16 => 
            array (
                'id' => 19,
                'parent_id' => 6,
            'label' => 'Jadwal Mengajar (Admin)',
                'icon' => 'pi pi-table',
                'to' => '/admin/monitoring/schedule-class',
                'sort_order' => 0,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-26 13:00:45',
                'updated_at' => '2025-12-26 13:00:45',
            ),
            17 => 
            array (
                'id' => 20,
                'parent_id' => 9,
                'label' => 'Managemen User',
                'icon' => 'pi pi-users',
                'to' => '/admin/users',
                'sort_order' => 1,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-27 05:27:16',
                'updated_at' => '2025-12-27 05:27:16',
            ),
            18 => 
            array (
                'id' => 21,
                'parent_id' => 15,
                'label' => 'Kur Dashboard',
                'icon' => 'pi pi-box',
                'to' => '/admin/curriculum/dashboard',
                'sort_order' => 3,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2025-12-28 02:17:52',
                'updated_at' => '2025-12-28 02:17:52',
            ),
            19 => 
            array (
                'id' => 22,
                'parent_id' => 6,
                'label' => 'Beban Mengajar Saya',
                'icon' => 'pi pi-chart-bar',
                'to' => '/teacher/curriculum/dashboard',
                'sort_order' => 2,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2026-07-13 06:04:00',
                'updated_at' => '2026-07-13 06:04:00',
            ),
            20 => 
            array (
                'id' => 23,
                'parent_id' => 6,
                'label' => 'Kalender Akademik',
                'icon' => 'pi pi-calendar',
                'to' => '/calendar/view',
                'sort_order' => 3,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2026-07-13 07:15:00',
                'updated_at' => '2026-07-13 07:15:00',
            ),
            21 => 
            array (
                'id' => 24,
                'parent_id' => NULL,
                'label' => 'Modul Surat',
                'icon' => 'pi pi-envelope',
                'to' => '/surat/dashboard',
                'sort_order' => 7,
                'is_separator' => false,
                'is_active' => true,
                'created_at' => '2026-08-02 10:00:00',
                'updated_at' => '2026-08-02 10:00:00',
            ),
        ));
        
        
    }
}