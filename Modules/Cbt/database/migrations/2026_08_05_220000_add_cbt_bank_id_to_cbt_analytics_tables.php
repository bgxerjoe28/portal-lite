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
        Schema::table('cbt_analysis_jobs', function (Blueprint $table) {
            $table->foreignId('cbt_exam_id')->nullable()->change();
            $table->foreignId('cbt_bank_id')->nullable()->after('cbt_exam_id')->constrained('cbt_banks')->onDelete('cascade');
        });

        Schema::table('cbt_ctt_item_analyses', function (Blueprint $table) {
            $table->foreignId('cbt_exam_id')->nullable()->change();
            $table->foreignId('cbt_bank_id')->nullable()->after('cbt_exam_id')->constrained('cbt_banks')->onDelete('cascade');
        });

        Schema::table('cbt_ctt_exam_summaries', function (Blueprint $table) {
            $table->foreignId('cbt_exam_id')->nullable()->change();
            $table->foreignId('cbt_bank_id')->nullable()->after('cbt_exam_id')->constrained('cbt_banks')->onDelete('cascade');
        });

        Schema::table('cbt_irt_item_parameters', function (Blueprint $table) {
            $table->foreignId('cbt_exam_id')->nullable()->change();
            $table->foreignId('cbt_bank_id')->nullable()->after('cbt_exam_id')->constrained('cbt_banks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbt_irt_item_parameters', function (Blueprint $table) {
            $table->dropForeign(['cbt_bank_id']);
            $table->dropColumn('cbt_bank_id');
        });

        Schema::table('cbt_ctt_exam_summaries', function (Blueprint $table) {
            $table->dropForeign(['cbt_bank_id']);
            $table->dropColumn('cbt_bank_id');
        });

        Schema::table('cbt_ctt_item_analyses', function (Blueprint $table) {
            $table->dropForeign(['cbt_bank_id']);
            $table->dropColumn('cbt_bank_id');
        });

        Schema::table('cbt_analysis_jobs', function (Blueprint $table) {
            $table->dropForeign(['cbt_bank_id']);
            $table->dropColumn('cbt_bank_id');
        });
    }
};
