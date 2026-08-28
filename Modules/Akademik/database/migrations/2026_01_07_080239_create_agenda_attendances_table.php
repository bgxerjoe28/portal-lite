<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_attendances', function (Blueprint $table) {
            $table->id();

            // Relasi ke agenda mengajar
            $table->foreignId('teaching_agenda_id')
                ->constrained('teaching_agendas')
                ->cascadeOnDelete();

            // Siswa yang dipresensi
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // Status kehadiran
            $table->boolean('is_present')->default(true);

            // Catatan opsional (izin / sakit / alpha)
            $table->string('note')->nullable();

            $table->timestamps();

            // 1 siswa hanya boleh 1 baris per agenda
            $table->unique(['teaching_agenda_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_attendances');
    }
};