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
        // 1. BANK SOAL (CbtBank)
        Schema::create('cbt_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. SOAL (CbtQuestion)
        Schema::create('cbt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_bank_id')->constrained('cbt_banks')->onDelete('cascade');
            $table->string('question_type'); // pilihan_ganda, isian_singkat, uraian, list, checklist, benar_salah, penjodohan, survey, skor_berbeda, sorting
            $table->text('question_text');
            $table->jsonb('options')->nullable(); // Menampung data pilihan/pernyataan/premis-target/item
            $table->jsonb('correct_answer')->nullable(); // Menampung kunci jawaban dinamis
            $table->decimal('score', 5, 2)->default(1.00); // Bobot nilai soal
            $table->timestamps();
        });

        // 3. SESI UJIAN / JADWAL (CbtExam)
        Schema::create('cbt_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_bank_id')->constrained('cbt_banks')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->string('title');
            $table->integer('duration'); // durasi dalam menit
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. KELAS PESERTA UJIAN (CbtExamClassroom - Pivot)
        Schema::create('cbt_exam_classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->timestamps();
        });

        // 5. PENGERJAAN UJIAN SISWA (CbtStudentExam)
        Schema::create('cbt_student_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('status')->default('not_started'); // not_started, started, submitted
            $table->jsonb('question_order')->nullable(); // Menyimpan urutan acak ID soal
            $table->jsonb('options_order')->nullable(); // Menyimpan urutan acak opsi per soal
            $table->timestamps();
        });

        // 6. DETAIL JAWABAN SISWA (CbtStudentAnswer)
        Schema::create('cbt_student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_student_exam_id')->constrained('cbt_student_exams')->onDelete('cascade');
            $table->foreignId('cbt_question_id')->constrained('cbt_questions')->onDelete('cascade');
            $table->jsonb('selected_answer')->nullable(); // Menyimpan jawaban terpilih/tertulis/dijodohkan/diurutkan
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 5, 2)->default(0.00);
            $table->boolean('is_doubtful')->default(false); // Menandai jika siswa ragu-ragu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbt_student_answers');
        Schema::dropIfExists('cbt_student_exams');
        Schema::dropIfExists('cbt_exam_classrooms');
        Schema::dropIfExists('cbt_exams');
        Schema::dropIfExists('cbt_questions');
        Schema::dropIfExists('cbt_banks');
    }
};
