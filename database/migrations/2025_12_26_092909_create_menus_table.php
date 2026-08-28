<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $col) {
            $col->id();
            $col->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade');
            $col->string('label');
            $col->string('icon')->nullable();
            $col->string('to')->nullable(); // Route Vue/PrimeVue
            $col->integer('sort_order')->default(0);
            $col->boolean('is_separator')->default(false);
            $col->boolean('is_active')->default(true);
            $col->timestamps();
        });

        // Pivot Table RBAC
        Schema::create('menu_role', function (Blueprint $col) {
            $col->foreignId('menu_id')->constrained()->onDelete('cascade');
            $col->foreignId('role_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus tabel pivot terlebih dahulu untuk menghindari constraint error
        Schema::dropIfExists('menu_role');

        // 2. Baru hapus tabel utama menus
        Schema::dropIfExists('menus');
    }
};
