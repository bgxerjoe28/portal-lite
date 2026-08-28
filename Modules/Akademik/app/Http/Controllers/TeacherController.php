<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\TeacherTemplateExport;
use Modules\Akademik\Imports\TeacherImport;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Teacher;
use Spatie\Permission\Models\Role;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validasi per_page (keamanan agar tidak jebol database)
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 50, 100])) {
            $perPage = 10;
        }

        // Mulai Query
        $query = Teacher::with('user')->orderBy('full_name', 'asc');

        // Logic Filter Sampah (Trash)
        if ($request->has('trash') && $request->trash == 'true') {
            $query->onlyTrashed(); // HANYA ambil yang sudah dihapus
        }

        // Logic Search (Tetap sama)
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ilike', '%'.$search.'%')
                    ->orWhere('nip', 'ilike', '%'.$search.'%');
            });
        }

        return Inertia::render('Akademik/TeacherIndex', [
            'teachers' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'trash', 'per_page']),
        ]);
    }

    // Kita siapkan method lain untuk step berikutnya (biarkan kosong dulu)
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email', // Email wajib unik
            'nip' => 'required|unique:teachers,nip', // NIP boleh kosong tapi jika ada harus unik
            'gender' => 'required|boolean',
            'phone' => 'nullable|string',
            'gelar_depan' => 'nullable|string',
            'gelar_belakang' => 'nullable|string',
        ]);

        // 2. Gunakan Transaksi Database (Agar User & Teacher tersimpan bareng)
        DB::transaction(function () use ($request) {

            // A. Buat Akun User Login
            $user = User::create([
                'name' => Str::title($request->full_name),
                'email' => $request->email,
                'password' => Hash::make('guru123'), // Password Default
                'password_must_change'=> true, // Paksa ganti password di login pertama
            ]);

            // B. Berikan Role "guru"
            $user->assignRole('guru');

            // C. Buat Profil Guru
            Teacher::create([
                'user_id' => $user->id,
                'full_name' => Str::title($request->full_name),
                'nip' => $request->nip,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'gelar_depan' => $request->gelar_depan,
                'gelar_belakang' => $request->gelar_belakang,
            ]);
        });

        // 3. Redirect kembali dengan pesan sukses
        return redirect()->back()->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);

        // 1. Validasi (Perhatikan Rule::unique yg mengabaikan ID saat ini)
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($teacher->user_id)],
            'nip' => ['required', Rule::unique('teachers')->ignore($teacher->id)],
            'gender' => 'required|boolean',
            'phone' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:10',
            'gelar_depan' => 'nullable|string|max:20',
        ]);

        DB::transaction(function () use ($request, $teacher) {
            // A. Update User (Email & Nama)
            $teacher->user->update([
                'name' => Str::title($request->full_name),
                'email' => $request->email,
            ]);

            // B. Update Profil Guru
            $teacher->update([
                'full_name' => Str::title($request->full_name),
                'nip' => $request->nip,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'gelar_belakang' => $request->gelar_belakang,
                'gelar_depan' => $request->gelar_depan,
            ]);
        });

        return redirect()->back()->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        // Cek Relasi Kelas (Cegah hapus jika masih jadi Wali Kelas Aktif)
        // Kita set NULL dulu jabatan wali kelasnya
        Classroom::where('teacher_id', $teacher->id)
            ->update(['teacher_id' => null]);

        DB::transaction(function () use ($teacher) {
            // Soft Delete User Login
            $teacher->user()->delete();
            // Soft Delete Profil Guru
            $teacher->delete();
        });

        return redirect()->back()->with('success', 'Guru berhasil dinonaktifkan (Masuk Sampah).');
    }

    // --- FITUR BARU: RESTORE ---
    public function restore($id)
    {
        // Cari di tong sampah
        $teacher = Teacher::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($teacher) {
            // Restore User Login
            $user = User::withTrashed()->find($teacher->user_id);
            if ($user) {
                $user->restore();
            }
            // Restore Profil Guru
            $teacher->restore();
        });

        return redirect()->back()->with('success', 'Data guru berhasil dipulihkan (Aktif Kembali).');
    }

    // --- FITUR OPSIONAL: FORCE DELETE (Hapus Permanen) ---
    // Nanti bisa ditambahkan jika ingin benar-benar menghapus selamanya
    public function forceDelete($id)
    {
        // Cari di tong sampah
        $teacher = Teacher::onlyTrashed()->with('user')->findOrFail($id);

        DB::transaction(function () use ($teacher) {
            // Hapus permanen User Login
            if ($teacher->user) {
                $user = User::withTrashed()->find($teacher->user_id);
                if ($user) {
                    $user->forceDelete();
                }
            }
            // Hapus permanen Profil Guru
            $teacher->forceDelete();
        });

        return redirect()->back()->with('success', 'Data guru berhasil dihapus permanen.');
    }

    // Method Download Template
    public function downloadTemplate()
    {
        return Excel::download(new TeacherTemplateExport, 'template_guru.xlsx');
    }

    // Method Proses Import
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new TeacherImport, $request->file('file'));

            return redirect()->back()->with('success', 'Data guru berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }
}
