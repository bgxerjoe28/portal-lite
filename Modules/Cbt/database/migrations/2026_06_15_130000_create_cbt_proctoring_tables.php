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
        // 1. Ruang Tes
        Schema::create('cbt_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity')->default(36);
            $table->timestamps();
        });

        // 2. Anggota Ruang Tes (Siswa & Kursi)
        Schema::create('cbt_room_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_room_id')->constrained('cbt_rooms')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->integer('seat_number'); // 1 to 36
            $table->timestamps();
            
            $table->unique(['cbt_room_id', 'seat_number']);
            $table->unique(['cbt_room_id', 'student_id']);
        });

        // 3. Pengawas & Jadwal Ruangan Sesi Ujian
        Schema::create('cbt_exam_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->foreignId('cbt_room_id')->constrained('cbt_rooms')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade'); // Pengawas
            $table->string('token', 10)->nullable();
            $table->dateTime('token_generated_at')->nullable();
            $table->string('status')->default('not_started'); // not_started, started, ended
            $table->timestamps();
            
            $table->unique(['cbt_exam_id', 'cbt_room_id']);
        });

        // 4. Tambahan Kolom di cbt_student_exams untuk Anti-Cheat & Proctoring
        Schema::table('cbt_student_exams', function (Blueprint $table) {
            $table->integer('warning_count')->default(0);
            $table->dateTime('blocked_until')->nullable();
            $table->boolean('is_blocked')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cbt_student_exams')) {
            Schema::table('cbt_student_exams', function (Blueprint $table) {
                $table->dropColumn(['warning_count', 'blocked_until', 'is_blocked']);
            });
        }
        
        Schema::dropIfExists('cbt_exam_rooms');
        Schema::dropIfExists('cbt_room_students');
        Schema::dropIfExists('cbt_rooms');
    }
};
