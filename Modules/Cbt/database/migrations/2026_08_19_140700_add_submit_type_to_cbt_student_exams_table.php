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
        if (Schema::hasTable('cbt_student_exams') && !Schema::hasColumn('cbt_student_exams', 'submit_type')) {
            Schema::table('cbt_student_exams', function (Blueprint $table) {
                $table->string('submit_type', 50)->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cbt_student_exams') && Schema::hasColumn('cbt_student_exams', 'submit_type')) {
            Schema::table('cbt_student_exams', function (Blueprint $table) {
                $table->dropColumn('submit_type');
            });
        }
    }
};
