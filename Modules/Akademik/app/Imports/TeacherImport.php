<?php

namespace Modules\Akademik\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\Teacher;

class TeacherImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Validasi sederhana: Skip jika nama/email kosong
            if (! isset($row['nama_lengkap']) || ! isset($row['email'])) {
                continue;
            }

            // Gunakan Transaksi agar data aman
            DB::transaction(function () use ($row) {
                // 1. Cek apakah email sudah ada?
                if (User::where('email', $row['email'])->exists()) {
                    return; // Skip data ini agar tidak error duplicate
                }

                // 2. Buat User
                $user = User::create([
                    'name' => Str::title($row['nama_lengkap']),
                    'email' => $row['email'],
                    'password' => Hash::make('guru123'), // Password Default
                    'password_must_change' => true,
                ]);

                // Assign Role
                $user->assignRole('guru');

                // 3. Buat Profil Guru
                Teacher::create([
                    'user_id' => $user->id,
                    'full_name' => Str::title($row['nama_lengkap']),
                    'nip' => $row['nip'] ?? null,
                    // Pastikan gender L/P, default L jika data ngaco
                    'gender' => isset($row['jenis_kelamin_lp']) && strtoupper($row['jenis_kelamin_lp']) == 'P' ? false : true,
                    'phone' => $row['no_hp'] ?? null,
                    'gelar_belakang' => $row['gelar_belakang'] ?? null,
                ]);
            });
        }
    }
}
