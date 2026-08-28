<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // 1. TAHUN BERAPA? (Wajib)
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');

            // 2. KELAS MANA? (Wajib)
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');

            // 3. MAPEL APA? (Wajib)
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            // 4. SIAPA GURUNYA? (Wajib)
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');

            // 5. KAPAN? (Opsional dulu, bisa diisi nanti saat plotting jadwal rinci)
            // Kalau tujuannya cuma buat Rapor, Hari/Jam tidak wajib.
            // Tapi kalau mau buat Jadwal Pelajaran visual, ini wajib.
            //$table->enum('day', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'])->nullable();
            //$table->time('start_time')->nullable();
            //$table->time('end_time')->nullable();

            //$table->string('description')->nullable(); // Catatan: misal "Ruang Lab Komputer"

            $table->timestamps();

            // OPTIMASI INDEX (Supaya query jadwal cepat)
            $table->index(['academic_year_id', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
