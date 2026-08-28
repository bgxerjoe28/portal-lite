<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    // 1. Tampilkan Halaman Login
    public function showLogin()
    {
        $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();
        $academicYearName = 'Belum Diset';
        if ($activeYear) {
            $academicYearName = $activeYear->name . ' - ' . ucfirst($activeYear->semester);
        }

        return Inertia::render('Auth/Login', [
            'academic_year' => $academicYearName
        ]);
    }

    // 2. Proses Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Email tidak ada ATAU password salah
        if (! $user || ! Hash::check($request->password, $user->password)) {
            \App\Services\ActivityLogger::log(
                'LOGIN_FAILED',
                'Percobaan login gagal untuk email: ' . $request->email
            );
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        // User ada tapi NONAKTIF
        if (! $user->is_active) {
            \App\Services\ActivityLogger::log(
                'LOGIN_FAILED',
                'Percobaan login ke akun non-aktif: ' . $request->email,
                $user
            );
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ]);
        }

        // Cek apakah situs sedang dalam mode pemeliharaan (Maintenance)
        if (\App\Models\Setting::get('site_active', '1') === '0' && ! $user->hasRole('admin')) {
            \App\Services\ActivityLogger::log(
                'LOGIN_FAILED',
                'Percobaan login saat Maintenance Mode ditolak: ' . $request->email,
                $user
            );
            throw ValidationException::withMessages([
                'email' => 'Situs sedang dinonaktifkan / dalam pemeliharaan (Maintenance Mode). Hanya Admin yang dapat masuk saat ini.',
            ]);
        }

        // Login manual (lebih terkontrol)
        Auth::login($user);
        $request->session()->regenerate();

        \App\Services\ActivityLogger::log(
            'LOGIN',
            'Pengguna berhasil login ke portal.',
            $user
        );

        // 🔐 WAJIB GANTI PASSWORD
        if ($user->password_must_change) {
            return redirect()->route('password.change');
        }
        if ($user->hasRole('admin')) {
            return redirect()->intended('/admin/dashboard');
        }

        if ($user->hasRole('guru')) {
            return redirect()->intended('/teacher/dashboard');
        }
        if ($user->hasRole('siswa')) {
            return redirect()->intended('/student/dashboard');
        }
        if ($user->hasRole('pegawai')) {
            return redirect()->intended('/staf/dashboard');
        }

        return redirect()->intended('/dashboard');
    }

    // 3. Proses Logout
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            \App\Services\ActivityLogger::log(
                'LOGOUT',
                'Pengguna keluar (logout) dari portal.',
                $user,
                null,
                null,
                $user
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'password_must_change' => false,
        ]);

        // 🔐 LOGOUT PAKSA (AMAN)
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Password berhasil diperbarui. Silakan login kembali.');
    }

    public function showChangePassword()
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function showProfile()
    {
        $user = Auth::user();
        
        $teacher = $user->teacher;
        $isTeacher = !is_null($teacher);
        
        $gender = null;
        if ($isTeacher) {
            $gender = (bool) $teacher->gender;
        } else {
            $gender = is_null($user->gender) ? null : (bool) $user->gender;
        }

        return Inertia::render('Profile/Show', [
            'profileUser' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->map(fn($n) => ucwords($n))->implode(', ') ?: 'User',
                'gender' => $gender,
                'is_teacher' => $isTeacher,
                'avatar_url' => $user->avatar_path ? \Illuminate\Support\Facades\Storage::url($user->avatar_path) : null,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $isTeacher = !is_null($user->teacher);

        $rules = [
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        if (!$isTeacher) {
            $rules['gender'] = ['nullable', 'boolean'];
        }

        $request->validate($rules);

        if (!$isTeacher && $request->has('gender')) {
            $user->gender = $request->gender === null ? null : filter_var($request->gender, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('avatar')) {
            $disk = config('filesystems.default');
            if ($user->avatar_path) {
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($user->avatar_path);
            }
            
            $file = $request->file('avatar');
            $tempPath = $file->getRealPath();
            $filename = 'avatars/' . uniqid() . '.jpg';
            
            $optimized = false;
            if (extension_loaded('gd')) {
                try {
                    $imageInfo = getimagesize($tempPath);
                    if ($imageInfo) {
                        $mime = $imageInfo['mime'];
                        $srcImg = null;
                        switch ($mime) {
                            case 'image/jpeg':
                            case 'image/jpg':
                                $srcImg = imagecreatefromjpeg($tempPath);
                                break;
                            case 'image/png':
                                $srcImg = imagecreatefrompng($tempPath);
                                break;
                            case 'image/gif':
                                $srcImg = imagecreatefromgif($tempPath);
                                break;
                            case 'image/webp':
                                $srcImg = imagecreatefromwebp($tempPath);
                                break;
                        }
                        
                        if ($srcImg) {
                            $origWidth = imagesx($srcImg);
                            $origHeight = imagesy($srcImg);
                            
                            $targetWidth = 300;
                            $targetHeight = 300;
                            
                            $ratio = $origWidth / $origHeight;
                            if ($targetWidth / $targetHeight > $ratio) {
                                $targetWidth = $targetHeight * $ratio;
                            } else {
                                $targetHeight = $targetWidth / $ratio;
                            }
                            
                            $dstImg = imagecreatetruecolor($targetWidth, $targetHeight);
                            
                            $white = imagecolorallocate($dstImg, 255, 255, 255);
                            imagefill($dstImg, 0, 0, $white);
                            
                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);
                            
                            ob_start();
                            imagejpeg($dstImg, null, 75);
                            $imageContent = ob_get_clean();
                            
                            imagedestroy($srcImg);
                            imagedestroy($dstImg);
                            
                            \Illuminate\Support\Facades\Storage::disk($disk)->put($filename, $imageContent);
                            $optimized = true;
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('GD Avatar Optimization failed: ' . $e->getMessage());
                }
            }
            
            if (!$optimized) {
                $path = $file->store('avatars', $disk);
                $user->avatar_path = $path;
            } else {
                $user->avatar_path = $filename;
            }
        }

        $user->save();

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
