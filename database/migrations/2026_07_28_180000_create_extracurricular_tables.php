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
        // 1. Tabel Daftar Ekstrakulikuler & Pembina (dikunci per Tahun Ajaran)
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable()->index(); // Guru Pembina
            $table->string('location')->nullable();
            $table->string('schedule_day')->nullable(); // misal: Jumat, Sabtu
            $table->string('schedule_time')->nullable(); // misal: 15:00 - 17:00
            $table->boolean('is_self_registration_open')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->onDelete('cascade');

            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->onDelete('set null');
        });

        // 2. Tabel Anggota Ekstrakulikuler Siswa
        Schema::create('extracurricular_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extracurricular_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('academic_year_id')->index();
            $table->timestamp('joined_at')->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            $table->foreign('extracurricular_id')
                ->references('id')
                ->on('extracurriculars')
                ->onDelete('cascade');

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->onDelete('cascade');

            $table->unique(['extracurricular_id', 'student_id', 'academic_year_id'], 'extracurr_student_ay_unique');
        });

        // 3. Tabel Sesi Presensi yang Dibuat Pembina
        Schema::create('extracurricular_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extracurricular_id')->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index(); // Guru Pembina yg membuat
            $table->date('date');
            $table->string('title');
            $table->string('token', 10)->index(); // Token 6 karakter alfanumerik uppercase
            $table->timestamp('token_expires_at')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->text('description')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->foreign('extracurricular_id')
                ->references('id')
                ->on('extracurriculars')
                ->onDelete('cascade');

            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->onDelete('set null');
        });

        // 4. Tabel Presensi Anggota Siswa pada Sesi
        Schema::create('extracurricular_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extracurricular_session_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('status', 30)->default('Hadir'); // Hadir, Izin, Sakit, Alpha
            $table->timestamp('check_in_time')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->foreign('extracurricular_session_id', 'ekstra_att_session_fk')
                ->references('id')
                ->on('extracurricular_sessions')
                ->onDelete('cascade');

            $table->foreign('student_id', 'ekstra_att_student_fk')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            $table->unique(['extracurricular_session_id', 'student_id'], 'ekstra_att_session_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracurricular_attendances');
        Schema::dropIfExists('extracurricular_sessions');
        Schema::dropIfExists('extracurricular_students');
        Schema::dropIfExists('extracurriculars');
    }
};
