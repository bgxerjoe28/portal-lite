<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Daftar Permission untuk Portal SMA
        $permissions = [
            'manage-users',
            'manage-students',
            'manage-grades',
            'access-penugasan',
            'manage-penugasan',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Buat Role Admin & Beri Semua Permission
        $roleAdmin = Role::findOrCreate('admin', 'web');
        $roleAdmin->givePermissionTo(Permission::all());

        // 3. Assign ke User Admin Pertama (Opsional)
        $user = User::where('email', 'admin@school.id')->first();
        if ($user) {
            $user->assignRole($roleAdmin);
        }
    }
}
