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
        // 1. Buat tabel cbt_sessions
        Schema::create('cbt_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });

        // 2. Modifikasi cbt_room_students (Tambah session)
        Schema::table('cbt_room_students', function (Blueprint $table) {
            // Drop unique index lama karena struktur penempatan berubah
            $table->dropUnique(['cbt_room_id', 'seat_number']);
            $table->dropUnique(['cbt_room_id', 'student_id']);
            
            // Tambahkan kolom session_id
            $table->foreignId('cbt_session_id')->nullable()->after('cbt_room_id')->constrained('cbt_sessions')->onDelete('cascade');
            
            // Siswa hanya boleh memiliki 1 kombinasi ruang dan sesi
            $table->unique(['student_id', 'cbt_session_id']);
            // Nomor bangku tidak boleh bentrok di ruang dan sesi yang sama
            $table->unique(['cbt_room_id', 'cbt_session_id', 'seat_number'], 'room_session_seat_unique');
        });

        // 3. Drop cbt_exam_rooms lama
        Schema::dropIfExists('cbt_exam_rooms');

        // 4. Buat tabel cbt_proctor_schedules
        Schema::create('cbt_proctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('cbt_room_id')->constrained('cbt_rooms')->onDelete('cascade');
            $table->foreignId('cbt_session_id')->constrained('cbt_sessions')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->string('token', 10)->nullable();
            $table->dateTime('token_generated_at')->nullable();
            $table->string('status')->default('not_started'); // not_started, started, ended
            $table->timestamps();
            
            // Satu ruang, di sesi tertentu, di hari tertentu, hanya punya 1 jadwal
            $table->unique(['date', 'cbt_room_id', 'cbt_session_id'], 'proctor_schedule_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbt_proctor_schedules');

        Schema::create('cbt_exam_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->foreignId('cbt_room_id')->constrained('cbt_rooms')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->string('token', 10)->nullable();
            $table->dateTime('token_generated_at')->nullable();
            $table->string('status')->default('not_started');
            $table->timestamps();
            $table->unique(['cbt_exam_id', 'cbt_room_id']);
        });

        Schema::table('cbt_room_students', function (Blueprint $table) {
            $table->dropForeign(['cbt_session_id']);
            $table->dropUnique('room_session_seat_unique');
            $table->dropUnique(['student_id', 'cbt_session_id']);
            
            $table->dropColumn('cbt_session_id');

            $table->unique(['cbt_room_id', 'seat_number']);
            $table->unique(['cbt_room_id', 'student_id']);
        });

        Schema::dropIfExists('cbt_sessions');
    }
};
