<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Konsolidasikan data duplikat jika ada record dengan selected_answer terisi
        DB::statement("
            UPDATE cbt_student_answers a
            SET selected_answer = b.selected_answer
            FROM cbt_student_answers b
            WHERE a.cbt_student_exam_id = b.cbt_student_exam_id
              AND a.cbt_question_id = b.cbt_question_id
              AND a.id > b.id
              AND a.selected_answer IS NULL
              AND b.selected_answer IS NOT NULL
        ");

        // 2. Pertahankan nilai poin atau status kebenaran tertinggi/tervalidasi jika sudah dinilai
        DB::statement("
            UPDATE cbt_student_answers a
            SET points_earned = GREATEST(a.points_earned, b.points_earned),
                is_correct = COALESCE(a.is_correct, b.is_correct)
            FROM cbt_student_answers b
            WHERE a.cbt_student_exam_id = b.cbt_student_exam_id
              AND a.cbt_question_id = b.cbt_question_id
              AND a.id > b.id
        ");

        // 3. Hapus baris duplikat yang lebih lama, pertahankan baris id yang paling baru
        DB::statement("
            DELETE FROM cbt_student_answers a
            USING cbt_student_answers b
            WHERE a.id < b.id
              AND a.cbt_student_exam_id = b.cbt_student_exam_id
              AND a.cbt_question_id = b.cbt_question_id
        ");

        // 4. Buat unique index untuk mencegah duplikasi di masa mendatang
        Schema::table('cbt_student_answers', function (Blueprint $table) {
            $table->unique(['cbt_student_exam_id', 'cbt_question_id'], 'cbt_student_answers_exam_question_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbt_student_answers', function (Blueprint $table) {
            $table->dropUnique('cbt_student_answers_exam_question_unique');
        });
    }
};
