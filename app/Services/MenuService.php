<?php

namespace App\Services;

class MenuService
{
    public static function menu(): array
    {
        return [
            // =========================
            // DASHBOARD
            // =========================
            [
                'label' => 'Dashboard',
                'icon'  => 'pi pi-home',
                'to'    => '/admin/dashboard',
                'roles' => ['admin'],
            ], [
                'label' => 'Dashboard',
                'icon'  => 'pi pi-home',
                'to'    => '/teacher/dashboard',
                'roles' => ['guru'],
            ], [
                'label' => 'Biodata Saya',
                'icon'  => 'pi pi-user',
                'to'    => '/student/profile',
                'roles' => ['siswa'],
            ],

            // =========================
            // ADMINISTRASI UTAMA
            // =========================
            [
                'label' => 'Administrasi Utama',
                'separator' => true,
                'roles' => ['admin'],
            ],
            [
                'label' => 'Administrasi Utama',
                'icon'  => 'pi pi-briefcase',
                'roles' => ['admin'],
                'children' => [
                    [
                        'label' => 'Tahun Ajaran & Mapel',
                        'icon'  => 'pi pi-calendar',
                        'to'    => '/admin/academic-years',
                    ],
                    [
                        'label' => 'Pendataan Guru',
                        'icon'  => 'pi pi-id-card',
                        'to'    => '/admin/teachers',
                    ],
                    [
                        'label' => 'Pendataan Siswa',
                        'icon'  => 'pi pi-users',
                        'to'    => '/admin/students',
                    ],
                    [
                        'label' => 'Manajemen Kelas',
                        'icon'  => 'pi pi-sitemap',
                        'to'    => '/admin/classrooms',
                    ],
                    [
                        'label' => 'Kenaikan Kelas',
                        'icon'  => 'pi pi-arrow-up-right',
                        'to'    => '/admin/classrooms/promotion',
                    ],
                ],
            ],

            // =========================
            // ADMINISTRASI KURIKULUM
            // =========================
            [
                'label' => 'Administrasi Kurikulum',
                'separator' => true,
                'roles' => ['admin'],
            ],
            [
                'label' => 'Administrasi Kurikulum',
                'icon'  => 'pi pi-bookmark',
                'roles' => ['admin'],
                'children' => [
                    [
                        'label' => 'Mata Pelajaran',
                        'icon'  => 'pi pi-verified',
                        'to'    => '/admin/subjects',
                    ],
                    [
                        'label' => 'Jam Mengajar',
                        'icon'  => 'pi pi-chart-bar',
                        'to'    => '/admin/schedules',
                    ],
                    [
                        'label' => 'Manajemen Kalender Akademik',
                        'icon'  => 'pi pi-calendar',
                        'to'    => '/admin/calendar',
                    ],
                ],
            ],

            // =========================
            // KBM & AGENDA GURU
            // =========================
            [
                'label' => 'KBM & Agenda Guru',
                'separator' => true,
                'roles' => ['admin', 'guru'],
            ],
            [
            'label' => 'Kalender Akademik',
            'icon' => 'pi pi-calendar',
            'to' => '/calendar/view',
            'roles' => ['admin', 'guru'],
            ],
            [
                'label' => 'KBM & Agenda Guru',
                'icon'  => 'pi pi-table',
                'roles' => ['admin', 'guru'],
                'children' => [
                    [
                        'label' => 'Jadwal Mengajar (Admin)',
                        'icon'  => 'pi pi-table',
                        'to'    => '/admin/monitoring/schedule-class',
                        'roles' => ['admin'],
                    ],
                    [
                        'label' => 'Jadwal Mengajar Saya',
                        'icon'  => 'pi pi-table',
                        'to'    => '/teacher/my-schedules',
                        'roles' => ['guru'],
                    ],
                    [
                        'label' => 'Input Agenda',
                        'icon'  => 'pi pi-book',
                        'to'    => '/academic/journal-input',
                        'roles' => ['guru'],
                    ],
                    [
                        'label' => 'Rekap Presensi',
                        'icon'  => 'pi pi-chart-bar',
                        'to'    => '/reports/attendance',
                        'roles' => ['admin', 'guru'],
                    ],
                ],
            ],

            // =========================
            // MODULE KURIKULUM
            // =========================
            [
                'label' => 'Module Kurikulum',
                'separator' => true,
                'roles' => ['admin'],
            ],
            [
                'label' => 'Module Kurikulum',
                'icon'  => 'pi pi-cog',
                'roles' => ['admin'],
                'children' => [
                    [
                        'label' => 'Capaian Pembelajaran',
                        'icon'  => 'pi pi-cog',
                        'to'    => '/curriculum/master',
                    ],
                ],
            ],

            // =========================
            // MODULE CBT
            // =========================
            [
                'label' => 'Module CBT',
                'separator' => true,
                'roles' => ['admin', 'guru'],
            ],
            [
                'label' => 'Module CBT',
                'icon'  => 'pi pi-server',
                'roles' => ['admin', 'guru'],
                'children' => [
                    [
                        'label' => 'Bank Soal',
                        'icon'  => 'pi pi-server',
                        'to'    => '/cbt/bank',
                        'roles' => ['admin', 'guru'],
                    ],
                    [
                        'label' => 'Jadwal Ujian',
                        'icon'  => 'pi pi-calendar',
                        'to'    => '/cbt/exams',
                        'roles' => ['admin', 'guru'],
                    ],
                    [
                        'label' => 'Pengawas Ujian',
                        'icon'  => 'pi pi-shield',
                        'to'    => '/cbt/proctor',
                        'roles' => ['admin', 'guru'],
                    ],
                ],
            ],

            // =========================
            // MODULE DAFTAR ULANG
            // =========================
            [
                'label' => 'Module Daftar Ulang',
                'separator' => true,
                'roles' => ['admin'],
            ],
            [
                'label' => 'Daftar Ulang',
                'icon'  => 'pi pi-user-plus',
                'roles' => ['admin'],
                'children' => [
                    [
                        'label' => 'Calon Murid Baru',
                        'icon'  => 'pi pi-users',
                        'to'    => '/admin/daftar-ulang',
                    ],
                ],
            ],
        ];
    }
}
