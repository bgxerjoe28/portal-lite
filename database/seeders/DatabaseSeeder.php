<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Religion;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\SubjectGroup;
use Spatie\Permission\Models\Role; // Model Pivot
use Modules\Kesiswaan\Database\Seeders\KesiswaanDatabaseSeeder;

// PENTING: Kita meload Model dari Namespace Modul Akademik
// Pastikan nanti file Model-nya sudah dibuat di Modules/Akademik/app/Models/

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // =============================
            // MASTER RELIGION
            // =============================
            $religions = [
                'Islam',
                'Protestan',
                'Katolik',
                'Hindu',
                'Buddha',
                'Konghucu',
            ];

            foreach ($religions as $r) {
                Religion::firstOrCreate(['name' => $r]);
            }
            // =============================
            // ROLE
            // =============================
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $guruRole  = Role::firstOrCreate(['name' => 'guru']);
            $siswaRole = Role::firstOrCreate(['name' => 'siswa']);

            // =============================
            // ADMIN
            // =============================
            $admin = User::firstOrCreate(
                ['email' => 'admin@sekolah.id'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $admin->assignRole('admin');

            // =============================
            // KEPALA SEKOLAH
            // =============================
            $kepsekRole = Role::firstOrCreate(['name' => 'kepala sekolah', 'guard_name' => 'web']);
            $kepsek = User::firstOrCreate(
                ['email' => 'kepsek@sekolah.id'],
                [
                    'name' => 'Bapak Kepala Sekolah',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $kepsek->assignRole('kepala sekolah');

            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
            $this->call(MenusTableSeeder::class);
            $this->call(MenuRoleTableSeeder::class);
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

            // =============================
            // SUBJECT GROUP
            // =============================
            $groups = [
                'Kelompok A (Wajib/Umum)',
                'Kelompok B (Kewilayahan)',
                'Kelompok C (Peminatan)',
                'Muatan Lokal',
                'Projek Penguatan Profil Pelajar Pancasila (P5)',
                'Bimbingan dan Konseling',
            ];

            foreach ($groups as $g) {
                SubjectGroup::firstOrCreate(['name' => $g]);
            }

            // =============================
            // ACADEMIC YEAR
            // =============================
            $year = AcademicYear::firstOrCreate([
                'name' => '2024/2025',
                'semester' => 'ganjil',
            ], [
                'is_active' => true,
            ]);

            // =============================
            // GURU
            // =============================
            $userGuru = User::firstOrCreate(
                ['email' => 'budi@sekolah.id'],
                [
                    'name' => 'Budi Santoso',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $userGuru->assignRole('guru');

            $teacher = Teacher::firstOrCreate([
                'user_id' => $userGuru->id,
            ], [
                'full_name' => 'Budi Santoso',
                'nip' => '198001012023011001',
                'gender' => true,
                'phone' => '081234567890',
                'gelar_belakang' => 'S.Pd',
            ]);

            // =============================
            // KELAS
            // =============================
            $kelas = Classroom::firstOrCreate([
                'academic_year_id' => $year->id,
                'name' => 'X IPA 1',
            ], [
                'teacher_id' => $teacher->id,
                'level' => 10,
                'major' => 'IPA',
            ]);

            // =============================
            // SISWA
            // =============================
            for ($i = 1; $i <= 5; $i++) {

                $userSiswa = User::firstOrCreate(
                    ['email' => "siswa$i@sekolah.id"],
                    [
                        'name' => "Siswa $i",
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );
                $userSiswa->assignRole('siswa');
                $islamId = Religion::where('name', 'Islam')->value('id');

                $siswa = Student::firstOrCreate([
                    'user_id' => $userSiswa->id,
                ], [
                    'full_name' => "Siswa Teladan $i",
                    'religion_id' => $islamId,
                    'gender' => $i % 2 == 0,
                    'nis' => "202400$i",
                    'nisn' => "001234567$i",
                    'phone' => '0812345600'.$i,
                    'birth_place' => 'Jakarta',
                    'birth_date' => '2008-01-01',
                    'address' => 'Jl. Pendidikan No. '.$i,
                ]);

                ClassroomStudent::firstOrCreate([
                    'academic_year_id' => $year->id,
                    'classroom_id' => $kelas->id,
                    'student_id' => $siswa->id,
                ], [
                    'status' => 'aktif',
                ]);
            }

            // Seed a test candidate student for Daftar Ulang module
            \Modules\DaftarUlang\Models\NewStudent::firstOrCreate([
                'no_pendaftaran' => 'PPDB2026001',
            ], [
                'academic_year_id' => $year->id,
                'ranking' => 1,
                'login_code' => 'DU2026',
                'status' => 'imported',
                'full_name' => 'BUDI UTOMO',
                'gender' => true,
                'nisn' => null,
            ]);
        });

        // Seed data master Modul Kesiswaan
        // (dijalankan di luar transaksi agar seeder individual bisa pakai transaksi sendiri)
        $this->call(KesiswaanDatabaseSeeder::class);
    }

}
