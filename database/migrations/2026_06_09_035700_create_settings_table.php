<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default values
        $defaults = [
            ['key' => 'school_name', 'value' => 'SMAN 16 SEMARANG', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'school_address', 'value' => 'Jl. Kedungmundu Raya No.123, Tembalang, Kota Semarang', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'school_phone', 'value' => '024-1234567', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'school_email', 'value' => 'info@sman16semarang.sch.id', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'principal_name', 'value' => 'Drs. H. Sukijo, M.Pd.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'principal_nip', 'value' => '196512121990031005', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_logo', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_favicon', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('settings')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
