<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage-users'),
        ];
    }

    /**
     * LIST + SEARCH + SORT
     */
    public function index(Request $request)
    {
        $activeTab = $request->query('role', 'all');
        $search = $request->query('search');
        $perPage = $request->integer('per_page', 10);

        $query = User::query()
            ->when($activeTab !== 'all', function ($q) use ($activeTab) {
                if ($activeTab === 'siswa') {
                    $q->role('siswa')->whereDoesntHave('student.classrooms', function ($q) {
                        $q->where('classroom_students.status', 'lulus');
                    });
                } elseif ($activeTab === 'alumni') {
                    $q->role('siswa')->whereHas('student.classrooms', function ($q) {
                        $q->where('classroom_students.status', 'lulus');
                    });
                } else {
                    $q->role($activeTab);
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->with('roles')
            ->latest();

        // 🔑 kalau pilih "semua"
        if ($perPage === -1) {
            $total = $query->count();

            $users = $query->paginate($total > 0 ? $total : 1)
                ->withQueryString();
        } else {
            $users = $query->paginate($perPage)
                ->withQueryString();
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'activeTab' => $activeTab,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
            'counts' => [
                'all' => User::count('*'),
                'admin' => User::role('admin')->count('*'),
                'guru' => User::role('guru')->count('*'),
                'siswa' => User::role('siswa')->whereDoesntHave('student.classrooms', function ($q) {
                        $q->where('classroom_students.status', 'lulus');
                    })->count('*'),
                'alumni' => User::role('siswa')->whereHas('student.classrooms', function ($q) {
                        $q->where('classroom_students.status', 'lulus');
                    })->count('*'),
                'pegawai' => User::role('pegawai')->count('*'),
                'kepala sekolah' => User::role('kepala sekolah')->count('*'),
                'guru bk' => User::role('guru bk')->count('*'),
            ],
        ]);
    }

    /**
     * TAMBAH USER
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,pegawai,kepala sekolah,guru bk',
            'password' => ['nullable', Password::defaults()],
        ]);

        $password = $request->password ?? Str::random(10);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'is_active' => true,
            'password_must_change' => true,
        ]);

        $user->assignRole($request->role);

        return back()->with('success', "User {$user->name} berhasil ditambahkan.");
    }

    /**
     * UPDATE USER (NON PASSWORD)
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => "required|email|unique:users,email,{$user->id}",
            'role' => 'required|exists:roles,name',
        ]);

        $user->update($request->only('name', 'email'));

        $user->syncRoles([$request->role]);

        return back()->with('success', "User {$user->name} berhasil diperbarui.");
    }

    /**
     * TOGGLE AKTIF / NONAKTIF
     */
    public function toggleStatus(User $user)
    {
        // dd($user);
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with('success', "Status {$user->name} berhasil diperbarui.");
    }

    /**
     * DISABLE BATCH
     */
    public function batchDisable(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        User::whereIn('id', $request->ids)
            ->where('id', '!=', Auth::id())
            ->update(['is_active' => false]);

        return back()->with('success', 'User terpilih berhasil dinonaktifkan.');
    }

    /**
     * ENABLE BATCH
     */
    public function batchEnable(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);
        User::whereIn('id', $request->ids)
            ->update(['is_active' => true]);

        return back()->with('success', 'User terpilih berhasil diaktifkan.');
    }

    /**
     * RESET PASSWORD
     */
    public function resetPassword(User $user)
    {
        // dd($request->password);
        if ($user->student()->exists()) {
            $password = 'Siswa123$';
        } elseif ($user->hasRole('kepala sekolah')) {
            $password = 'Kepsek16!@3';
        } else {
            // Asumsi jika bukan siswa atau kepsek, maka guru/staf/admin
            $password = 'Guru16!@34';
        }

        $user->update([
            'password' => Hash::make($password),
            'password_must_change' => true,
        ]);

        return back()
            ->with('success', "Password {$user->name} berhasil direset. Password baru: {$password}");
    }
}
