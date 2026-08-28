<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TAHUN AJARAN (Contoh: 2024/2025 - Ganjil)
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 2024/2025
            $table->enum('semester', ['ganjil', 'genap']);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. MATA PELAJARAN
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // MTK, IND
            $table->string('name');
            // --- TAMBAHAN BARU ---
            // Penting untuk Rapor (Kelompok A, B, C / Muatan Lokal)
            $table->string('group')->default('A');
            $table->timestamps();
            // --- TAMBAHAN BARU ---
            // Agar aman tidak hilang permanen saat dihapus
            $table->softDeletes();

        });

        // 3. DATA GURU
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nip')->nullable()->unique();
            $table->string('full_name');
            $table->string('gelar_depan', 10)->nullable();
            $table->string('gelar_belakang', 20)->nullable();
            $table->boolean('gender')->default(true); // 1=L, 0=P. Default L.
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 4. DATA KELAS / ROMBEL
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers'); // Wali Kelas
            $table->string('name', 15); // X IPA 1
            $table->tinyInteger('level'); // 10, 11, 12
            $table->string('major')->nullable();
            $table->timestamps();
        });

        // 5. DATA SISWA
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->string('nis')->unique()->nullable();
            $table->string('nisn')->unique();
            $table->string('full_name');
            $table->boolean('gender')->default(true); // 1=L, 0=P. Default L.
            $table->enum('agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan']);
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('nama_ibu')->nullable();
            $table->text('nama_ayah')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 6. JADWAL PELAJARAN
        Schema::create('teaching_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('day_of_week', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // 7. JURNAL MENGAJAR
        Schema::create('teacher_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_schedule_id')->constrained('teaching_schedules')->onDelete('cascade');
            $table->date('date');
            $table->text('topic');
            $table->text('notes')->nullable();
            $table->enum('status', ['terlaksana', 'tugas', 'kosong'])->default('terlaksana');

            // Integrasi Kurikulum & CBT (Nullable dulu)
            $table->unsignedBigInteger('cp_id')->nullable();
            $table->unsignedBigInteger('tp_id')->nullable();

            $table->timestamps();
        });

        // 8. PRESENSI SISWA
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_journal_id')->constrained('teacher_journals')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha']);
            $table->integer('minutes_late')->default(0); // Fitur Terlambat
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('teacher_journals');
        Schema::dropIfExists('teaching_schedules');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('academic_years');
    }
};
