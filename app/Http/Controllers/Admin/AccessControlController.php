<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Nwidart\Modules\Facades\Module;

class AccessControlController extends Controller
{

    public function index()
    {
        // Pastikan fitur utama selalu ada di database
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage-permits', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage-lates', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage-facility-reports', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage-discipline', 'guard_name' => 'web']);

        $permissions = \Spatie\Permission\Models\Permission::with(['users.teacher', 'users.roles'])->get()->map(function($perm) {
            return [
                'id' => $perm->id,
                'name' => $perm->name,
                'users' => $perm->users->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role_type' => $user->roles->pluck('name')->map(fn($n) => ucwords($n))->implode(', ') ?: 'Pegawai',
                ])
            ];
        });

        $userOptions = \App\Models\User::query()
                ->whereDoesntHave('student') // 🔑 KUNCI: Jangan tarik user yang merupakan siswa
                ->with(['teacher', 'roles'])
                ->get()
                ->map(function($user) {
                    // Gunakan role dari Spatie
                    $rolesList = $user->roles->pluck('name')->map(fn($n) => strtoupper($n))->implode(', ') ?: 'PEGAWAI';
                    $type = "[{$rolesList}]";
                    $nip = $user->teacher?->nip ?? '-';
                    
                    return [
                        'value' => $user->id,
                        'label' => "{$type} {$user->name} ({$nip})",
                        'name' => $user->name,
                        'type' => $type
                    ];
                });
        //dd($permissions);

        return Inertia::render('Admin/AccessControl/Index', [
            'permissions' => $permissions,
            'userOptions' => $userOptions
        ]);
    }

    // Menambah macam permission baru (misal: manage-inventory, dsb)
    public function storePermission(Request $request)
    {
        $request->validate(['name' => 'required|unique:permissions,name']);
        Permission::create(['name' => $request->name, 'guard_name' => 'web']);
        return redirect()->back()->with('success', 'Fitur baru berhasil ditambahkan');
    }

    // Menambahkan guru ke dalam permission tertentu (mendukung multi-select)
    public function assignUser(Request $request)
    {
        $request->validate([
            'permission_id' => 'required',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $permission = is_numeric($request->permission_id) 
            ? \Spatie\Permission\Models\Permission::findOrFail($request->permission_id)
            : \Spatie\Permission\Models\Permission::where('name', $request->permission_id)->firstOrFail();
        
        foreach ($request->user_ids as $userId) {
            $user = \App\Models\User::findOrFail($userId);
            $user->givePermissionTo($permission);
        }

        return redirect()->back()->with('success', 'Personil berhasil ditambahkan ke fitur');
    }

    // Menghapus guru dari permission
    public function revokeUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_name' => 'required'
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        
        // 🔑 PERBAIKAN: Cari berdasarkan NAME, bukan ID
        $permission = \Spatie\Permission\Models\Permission::where('name', $request->permission_name)
            ->where('guard_name', 'web')
            ->firstOrFail();

        $user->revokePermissionTo($permission);

        return redirect()->back()->with('success', 'Akses personil berhasil dicabut');
    }

    // Sinkronisasi seluruh modul yang ada ke tabel permissions
    public function syncModules()
    {
        $modules = Module::all();
        $createdCount = 0;

        foreach ($modules as $module) {
            $moduleNameLower = strtolower($module->getName());

            // 1. Permission untuk akses modul
            $accessPerm = Permission::firstOrCreate([
                'name' => "access-{$moduleNameLower}",
                'guard_name' => 'web'
            ]);
            if ($accessPerm->wasRecentlyCreated) {
                $createdCount++;
            }

            // 2. Permission untuk kelola modul
            $managePerm = Permission::firstOrCreate([
                'name' => "manage-{$moduleNameLower}",
                'guard_name' => 'web'
            ]);
            if ($managePerm->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        if ($createdCount > 0) {
            return redirect()->back()->with('success', "Berhasil mensinkronisasi modul. {$createdCount} permission baru ditambahkan.");
        }

        return redirect()->back()->with('success', 'Semua modul sudah tersinkronisasi.');
    }
}