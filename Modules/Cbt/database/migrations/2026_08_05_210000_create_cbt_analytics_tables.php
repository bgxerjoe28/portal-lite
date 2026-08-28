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
        // 1. TRACKING JOB ANALISIS (CbtAnalysisJob)
        Schema::create('cbt_analysis_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->string('job_type'); // CTT, IRT_1PL, IRT_2PL, IRT_3PL
            $table->string('status')->default('queued'); // queued, processing, sent_to_microservice, completed, failed
            $table->integer('total_participants')->default(0);
            $table->integer('progress_percent')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 2. HASIL PENILAIAN CTT SISWA (CbtCttStudentResult)
        Schema::create('cbt_ctt_student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_student_exam_id')->constrained('cbt_student_exams')->onDelete('cascade');
            $table->decimal('raw_score', 8, 2)->default(0.00);
            $table->decimal('max_score', 8, 2)->default(0.00);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->integer('correct_count')->default(0);
            $table->integer('wrong_count')->default(0);
            $table->integer('unanswered_count')->default(0);
            $table->integer('rank')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        // 3. ANALISIS BUTIR SOAL CTT (CbtCttItemAnalysis)
        Schema::create('cbt_ctt_item_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->foreignId('cbt_question_id')->constrained('cbt_questions')->onDelete('cascade');
            $table->decimal('difficulty_index', 5, 4)->default(0.0000); // p = R / N (0 - 1)
            $table->decimal('discrimination_index', 5, 4)->default(0.0000); // D = P_Upper - P_Lower
            $table->decimal('point_biserial', 5, 4)->nullable(); // r_pbis (-1 s/d +1)
            $table->jsonb('distractor_stats')->nullable(); // Frekuensi & % setiap opsi A, B, C, D, E
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        // 4. RINGKASAN ANALISIS CTT UJIAN (CbtCttExamSummary)
        Schema::create('cbt_ctt_exam_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->decimal('cronbach_alpha', 5, 4)->nullable(); // Reliabilitas soal (0 - 1)
            $table->decimal('mean_score', 8, 2)->default(0.00);
            $table->decimal('median_score', 8, 2)->default(0.00);
            $table->decimal('std_deviation', 8, 2)->default(0.00);
            $table->decimal('min_score', 8, 2)->default(0.00);
            $table->decimal('max_score', 8, 2)->default(0.00);
            $table->integer('total_participants')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        // 5. PARAMETER BUTIR SOAL IRT (CbtIrtItemParameter)
        Schema::create('cbt_irt_item_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->onDelete('cascade');
            $table->foreignId('cbt_question_id')->constrained('cbt_questions')->onDelete('cascade');
            $table->string('model_type')->default('2PL'); // 1PL_Rasch, 2PL, 3PL
            $table->decimal('difficulty_b', 6, 4)->default(0.0000); // Parameter Kesukaran b (-4 s/d +4)
            $table->decimal('discrimination_a', 6, 4)->default(1.0000); // Parameter Daya Beda a (default 1.0)
            $table->decimal('guessing_c', 6, 4)->default(0.0000); // Parameter Tebakan Pseudo c (default 0.0)
            $table->decimal('infit_mnsq', 5, 4)->nullable(); // Infit Mean Square (khusus Rasch)
            $table->decimal('outfit_mnsq', 5, 4)->nullable(); // Outfit Mean Square (khusus Rasch)
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        // 6. KEMAMPUAN SISWA IRT (CbtIrtStudentAbility)
        Schema::create('cbt_irt_student_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_student_exam_id')->constrained('cbt_student_exams')->onDelete('cascade');
            $table->decimal('theta', 6, 4)->default(0.0000); // Ability Laten θ (-4.0 s/d +4.0)
            $table->decimal('standard_error', 6, 4)->default(0.0000); // SE(θ)
            $table->decimal('scaled_score', 8, 2)->default(0.00); // Skor skala (misal 200 - 800)
            $table->decimal('percentile', 5, 2)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbt_irt_student_abilities');
        Schema::dropIfExists('cbt_irt_item_parameters');
        Schema::dropIfExists('cbt_ctt_exam_summaries');
        Schema::dropIfExists('cbt_ctt_item_analyses');
        Schema::dropIfExists('cbt_ctt_student_results');
        Schema::dropIfExists('cbt_analysis_jobs');
    }
};
