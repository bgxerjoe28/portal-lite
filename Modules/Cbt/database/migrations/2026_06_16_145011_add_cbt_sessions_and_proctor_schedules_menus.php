<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Menu;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $guruRole = Role::where('name', 'guru')->first();

        // 52 = parent id for "Ujian (CBT)" based on previous checks
        $parentCbt = Menu::find(52); 

        if ($parentCbt) {
            $sessionMenu = Menu::create([
                'parent_id' => $parentCbt->id,
                'label' => 'Sesi Ujian',
                'icon' => 'pi pi-clock',
                'to' => '/cbt/sessions',
                'sort_order' => 2,
                'is_separator' => false,
                'is_active' => true,
            ]);

            if ($adminRole) $sessionMenu->roles()->attach($adminRole->id);

            $proctorScheduleMenu = Menu::create([
                'parent_id' => $parentCbt->id,
                'label' => 'Jadwal Pengawas',
                'icon' => 'pi pi-calendar-times',
                'to' => '/cbt/proctor-schedules',
                'sort_order' => 4,
                'is_separator' => false,
                'is_active' => true,
            ]);

            if ($adminRole) $proctorScheduleMenu->roles()->attach($adminRole->id);
            if ($guruRole) $proctorScheduleMenu->roles()->attach($guruRole->id);
        }
    }

    public function down(): void
    {
        Menu::where('to', '/cbt/sessions')->delete();
        Menu::where('to', '/cbt/proctor-schedules')->delete();
    }
};
