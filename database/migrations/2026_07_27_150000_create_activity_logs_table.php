<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('role', 50)->nullable()->index();
            $table->string('action', 100)->index();
            $table->text('description');
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        // Add Menu item under 'Pengaturan'
        $adminRole = Role::where('name', 'admin')->first();
        $ksRole = Role::where('name', 'kepala sekolah')->first();

        $parentMenu = Menu::where('label', 'Pengaturan')->whereNull('parent_id')->first();
        if ($parentMenu && $adminRole) {
            $childMenu = Menu::create([
                'parent_id' => $parentMenu->id,
                'label' => 'Log Aktivitas & Audit',
                'icon' => 'pi pi-history',
                'to' => '/admin/activity-logs',
                'sort_order' => 10,
                'is_separator' => false,
                'is_active' => true
            ]);

            $childMenu->roles()->attach($adminRole);
            if ($ksRole) {
                $childMenu->roles()->attach($ksRole);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        $menu = Menu::where('to', '/admin/activity-logs')->first();
        if ($menu) {
            $menu->delete();
        }
    }
};
