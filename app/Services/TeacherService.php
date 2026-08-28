<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;

class TeacherService
{
    /**
     * Mengambil ID Guru yang sedang login
     */
    public static function getAuthTeacherId(): ?int
    {
        return Auth::user()?->teacher?->id;
    }

    /**
     * Mengambil objek Guru lengkap dengan relasinya
     */
    public static function getAuthTeacher()
    {
        return Auth::user()?->teacher;
    }
}