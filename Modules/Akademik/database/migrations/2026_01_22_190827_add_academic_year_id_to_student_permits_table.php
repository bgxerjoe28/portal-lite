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
        Schema::table('student_permits', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->after('student_id')
                ->nullable() // Gunakan nullable jika tabel sudah berisi data
                ->constrained('academic_years')
                ->onDelete('cascade');
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_permits', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');

            // Mengembalikan foreign key ke tabel teachers jika di-rollback
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->onDelete('cascade');
        });
    }
};
