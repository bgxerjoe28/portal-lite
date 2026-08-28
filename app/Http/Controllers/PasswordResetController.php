<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Modules\Akademik\Models\Student;

class PasswordResetController extends Controller
{
    public function showVerifyForm()
    {
        return Inertia::render('Auth/VerifyStudentReset');
    }

    public function showForgotEmailForm()
    {
        return Inertia::render('Auth/ForgotEmail');
    }

    public function lookupEmail(Request $request)
    {
        $request->validate([
            'nisn' => 'required|string',
            'birth_date' => 'required|date_format:Y-m-d',
        ]);

        $student = Student::where('nisn', $request->nisn)
            ->where('birth_date', $request->birth_date)
            ->first();

        if (! $student || ! $student->user) {
            return back()->withErrors(['message' => 'Data NISN atau Tanggal Lahir tidak cocok dengan sistem.']);
        }

        return Inertia::render('Auth/ForgotEmail', [
            'found_email' => $student->user->email,
            'student_name' => $student->full_name,
        ]);
    }

    public function verifyAndReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'birth_date' => 'required|date_format:Y-m-d',
        ]);

        // 1. Cari siswa berdasarkan email user-nya
        $student = Student::whereHas('user', function ($q) use ($request) {
            $q->where('email', $request->email);
        })
            ->where('birth_date', $request->birth_date) // Cocokkan Tanggal Lahir
            ->first();

        if (! $student) {
            return back()->withErrors(['message' => 'Data Email atau Tanggal Lahir tidak cocok dengan sistem. Atau Anda bukan siswa']);
        }

        // 2. Generate Password Random sesuai permintaan Bapak
        $pool = ['Sman16123', 'SMAN16123', 'Sman16@123', 'Sman16#123', 'Sman16$123', 'Sman16!123', 'Sman16_123', 'Sman16*123', 'Sman16Admin123', 'Sman16Portal123', 'Sman16Update123',
            'Siswa123$', 'Siswa@123', 'Siswa#123', 'Siswa$1234', 'Siswa16$', 'SiswaSman16$', 'SiswaPortal16$', 'SiswaUpdate16$', 'Siswa2024$', 'Siswa2025$',
            'Portal16#', 'Portal#16', 'Portal16@#', 'Portal16Admin#', 'Portal16Sman#', 'Portal16Update#', 'Portal16User#', 'Portal16Login#', 'PortalSman16#', 'Portal16#123',
            'Sman16Update', 'Sman16Update1', 'Sman16Update!', 'Sman16Update#', 'Sman16Update2024', 'Sman16Update2025', 'UpdateSman16', 'UpdatePortal16', 'UpdateSiswa16', 'Sman16Update@123',
            'Sman16Portal123', 'Portal16Siswa123', 'Siswa16Portal#', 'Sman16#Portal', 'Portal#Sman16', 'Sman16Siswa123', 'Portal16Update123', 'Update16Portal#', 'Sman16PortalUpdate', 'PortalUpdate16',
            'Sman16@Portal123', 'Portal16#Siswa2025', 'Siswa16$Update!', 'Sman16#AdminPortal', 'Portal16@Update123', 'SiswaPortal16#2025', 'Sman16Update@Portal', 'Portal#Sman16$123', 'Siswa16#Portal!', 'Sman16Portal#2025'];
        $newPassword = Arr::random($pool).rand(10, 99); // Gabungan kata + angka acak

        // 3. Update Password di tabel Users
        $user = $student->user;
        $user->password = Hash::make($newPassword);
        $user->save();

        // 4. Kirim ke halaman sukses sambil membawa password barunya
        return Inertia::render('Auth/ResetSuccess', [
            'newPassword' => $newPassword,
            'studentName' => $student->full_name,
        ]);
    }
}
