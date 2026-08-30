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
        if (Schema::hasTable('new_students')) {
            Schema::table('new_students', function (Blueprint $table) {
                $table->string('jenis_kejuaraan')->nullable()->after('prev_school_name');
                $table->string('nilai')->nullable()->after('phone');
                $table->string('jarak')->nullable()->after('nilai');
                $table->string('umur')->nullable()->after('jarak');
                $table->string('nilai_akhir')->nullable()->after('umur');
            });
        }

        Schema::table('students', function (Blueprint $table) {
            $table->string('jenis_kejuaraan')->nullable()->after('prev_school_name');
            $table->string('nilai')->nullable()->after('periodik_phone');
            $table->string('jarak')->nullable()->after('nilai');
            $table->string('umur')->nullable()->after('jarak');
            $table->string('nilai_akhir')->nullable()->after('umur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('new_students')) {
            Schema::table('new_students', function (Blueprint $table) {
                $table->dropColumn(['jenis_kejuaraan', 'nilai', 'jarak', 'umur', 'nilai_akhir']);
            });
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['jenis_kejuaraan', 'nilai', 'jarak', 'umur', 'nilai_akhir']);
        });
    }
};
