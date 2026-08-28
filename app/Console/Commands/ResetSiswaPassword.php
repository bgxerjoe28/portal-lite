<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetSiswaPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:resetsiswa
                            {--password=Siswa123$ : Password baru untuk semua siswa (default: Siswa123$)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password semua siswa tanpa meminta perubahan password (hanya untuk server staging dan lokal)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment('production', 'prod')) {
            $this->error('ERROR: Command ini dilarang dan tidak boleh dijalankan di environment produksi (production)!');
            return self::FAILURE;
        }

        if (! app()->environment(['local', 'staging', 'testing'])) {
            $this->error('ERROR: Command ini hanya boleh dijalankan di server staging dan lokal!');
            $this->error('Environment saat ini: ' . app()->environment());
            return self::FAILURE;
        }

        $userIds = User::where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'siswa');
            })->orWhereHas('student');
        })->pluck('id');

        if ($userIds->isEmpty()) {
            $this->warn('Tidak ada data siswa yang ditemukan.');
            return self::SUCCESS;
        }

        $count = $userIds->count();
        $password = $this->option('password') ?: 'Siswa123$';
        $hashedPassword = Hash::make($password);

        $this->info("Memulai reset password untuk {$count} siswa...");

        $updated = 0;
        foreach ($userIds->chunk(500) as $chunk) {
            $updated += User::whereIn('id', $chunk)->update([
                'password' => $hashedPassword,
                'password_must_change' => false,
            ]);
        }

        $this->info("Berhasil mereset password {$updated} siswa menjadi: {$password}");
        $this->info("Status 'password_must_change' diatur menjadi false (tanpa meminta perubahan password).");

        return self::SUCCESS;
    }
}
