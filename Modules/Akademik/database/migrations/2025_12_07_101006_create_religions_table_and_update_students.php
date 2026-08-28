<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Tabel Referensi Agama
        Schema::create('religions', function (Blueprint $table) {
            $table->tinyIncrements('id'); // ID otomatis 1, 2, 3... (Max 255)
            $table->string('name', 50);
            $table->timestamps();
        });

        // 2. Ubah Tabel Siswa
        Schema::table('students', function (Blueprint $table) {
            // Hapus kolom string lama
            $table->dropColumn('agama');

            // Tambah kolom ID baru (nullable, jaga-jaga kalau data kosong)
            $table->unsignedTinyInteger('religion_id')->nullable()->after('gender');

            // Relasi (Optional, tapi bagus buat constraint)
            $table->foreign('religion_id')->references('id')->on('religions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['religion_id']);
            $table->dropColumn('religion_id');
            $table->string('agama')->nullable();
        });
        Schema::dropIfExists('religions');
    }
};
