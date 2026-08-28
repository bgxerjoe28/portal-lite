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
        // 1. Tabel Komponen Penilaian (Setting Bobot)
        Schema::create('grading_components', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Tugas, UTS, UAS, Keaktifan
            $table->integer('weight'); // Contoh: 30 (dalam persen)
            
            // Relasi ke Modul Akademik
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // Guru pengampu
            
            $table->timestamps();
        });

        // 2. Tabel Nilai Siswa
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('grading_component_id')->constrained('grading_components')->onDelete('cascade');
            
            $table->decimal('score', 5, 2); // Nilai 0-100 (misal: 85.50)
            $table->text('note')->nullable(); // Catatan guru untuk siswa
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop tabel nilai siswa dulu (karena depend ke grading_components)
        Schema::dropIfExists('student_grades');

        // 2. Drop tabel komponen penilaian
        Schema::dropIfExists('grading_components');
    }
};
