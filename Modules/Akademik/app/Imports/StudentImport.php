<?php

namespace Modules\Akademik\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Student;

class StudentImport implements ToCollection, WithHeadingRow
{
    private $religions;

    public function __construct()
    {
        // Ambil semua agama dari DB dan jadikan Array [ 'islam' => 1, 'kristen' => 2, ... ]
        // Pluck name sebagai key (lowercase) dan id sebagai value
        $this->religions = \Modules\Akademik\Models\Religion::all()
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])
            ->toArray();
    }

    private function getReligionId($input)
    {
        if (empty($input)) {
            return null;
        }
        $text = strtolower(trim($input));

        // Normalisasi Typo (Mapping Manual ke Key yang Benar)
        if (str_contains($text, 'katolik') || str_contains($text, 'katholik')) {
            $text = 'katolik';
        }
        if (str_contains($text, 'kristen')) {
            $text = 'protestan';
        }
        if (str_contains($text, 'budha')) {
            $text = 'buddha';
        } // Typo umum

        // Cari ID di array
        return $this->religions[$text] ?? null;
    }

    public function collection(Collection $rows)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new \Exception('Belum ada Tahun Ajaran yang aktif.');
        }
        // set defaul password
        $defaultPassword = Hash::make('Siswa123');

        // --- OPTIMASI 1: Cache Data Agama ke Memory (Biar gak query 639 kali) ---
        // (Pastikan Anda sudah punya logic getReligionId seperti diskusi sebelumnya)
        // $this->loadReligions();

        // --- OPTIMASI 2: TRANSAKSI DI LUAR LOOP ---
        DB::beginTransaction(); // Mulai 1 Transaksi Besar

        try {
            collect($rows)->chunk(50)->each(function ($chunk) use ($defaultPassword) {

                foreach ($chunk as $index => $row) {

                    $namaRaw = $row['nama_lengkap'] ?? null;
                    $nisnRaw = $row['nisn'] ?? null;

                    if (empty($namaRaw)) {
                        continue;
                    }

                    $email = $row['email'] ?? strtolower(trim($nisnRaw)).'@sman16.id';
                    $namaClean = Str::title(trim($namaRaw));

                    $user = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $namaClean,
                            'password' => $defaultPassword, // 🔥 pakai hash cache
                            'password_must_change' => true,
                        ]
                    );

                    if (! $user->hasRole('siswa')) {
                        $user->assignRole('siswa');
                    }

                    Student::updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'full_name' => Str::upper($namaClean),
                            'nis' => $row['nis'] ?? null,
                            'nisn' => $nisnRaw,
                            'gender' => isset($row['jenis_kelamin']) && strtoupper($row['jenis_kelamin']) === 'P' ? false : true,
                            'religion_id' => $this->getReligionId($row['agama']),
                            'phone' => $row['no_hp'] ?? null,
                        ]
                    );
                }

            });
            /* --- LAMA (SLOW) ---
            foreach ($rows as $index => $row) {

                // ... Logic Validasi & Sanitasi Data (Sama seperti sebelumnya) ...
                $namaRaw = $row['nama_lengkap'] ?? null;
                $nisnRaw = $row['nisn'] ?? null;

                if (empty($namaRaw)) {
                    continue;
                }

                // --- LOGIC SIMPAN (Langsung, tanpa DB::transaction lagi) ---

                // 1. User
                $email = $row['email'] ?? strtolower(trim($nisnRaw)).'@sman16.id';
                $namaClean = Str::title(trim($namaRaw));

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $namaClean,
                        // 'password' => Hash::make('siswa123'), // Ini yang berat, tapi wajar
                        'password' => $defaultPassword, // Gunakan password default yang sudah di-hash
                    ]
                );

                if (! $user->hasRole('siswa')) {
                    $user->assignRole('siswa');
                }

                // 2. Student
                Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'full_name' => Str::upper($namaClean),
                        'nis' => $row['nis'] ?? null,
                        'nisn' => $nisnRaw,
                        'gender' => isset($row['jenis_kelamin']) && strtoupper($row['jenis_kelamin']) == 'P' ? false : true, // Boolean
                        'religion_id' => $this->getReligionId($row['agama']), // Pakai fungsi ID
                        'phone' => $row['no_hp'] ?? null,
                    ]
                );
            }*/

            DB::commit(); // Simpan SEMUANYA sekaligus di akhir (Cepat!)

        } catch (\Exception $e) {
            DB::rollBack(); // Jika 1 gagal, batalkan semua

            // Tampilkan error biar tau baris mana yang bikin masalah
            throw new \Exception('Gagal Import pada baris ke-'.($index + 2).'. Error: '.$e->getMessage());
        }
    }
}
