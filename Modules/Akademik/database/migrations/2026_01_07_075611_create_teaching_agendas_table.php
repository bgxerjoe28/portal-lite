<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_agendas', function (Blueprint $table) {
            $table->id();

            // Tahun ajaran aktif (diambil dari sistem, disimpan untuk audit)
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

            // Mengikat ke jadwal mengajar (mencegah input agenda di luar jadwal)
            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->cascadeOnDelete();

            // Snapshot identitas mengajar (biar cepat query + aman kalau jadwal berubah)
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->foreignId('classroom_id')
                ->constrained('classrooms')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            // TP yang dipakai (boleh null kalau guru belum pilih TP)
            $table->foreignId('learning_objective_tp_id')
                ->nullable()
                ->constrained('learning_objectives_tp')
                ->nullOnDelete();

            // Waktu pelaksanaan
            $table->date('date');
            $table->string('day', 10); // Senin, Selasa, dst

            // Isi agenda
            $table->text('materi_pembelajaran');
            $table->text('keterangan')->nullable();

            $table->timestamps();

            // Index yang sering dipakai filter
            $table->index(['teacher_id', 'date']);
            $table->index(['classroom_id', 'date']);
            $table->index(['subject_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_agendas');
    }
};