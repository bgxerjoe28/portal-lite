<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            // 1. Hapus kolom lama jika masih ada
            if (Schema::hasColumn('student_grades', 'grading_component_id')) {
                $table->dropForeign(['grading_component_id']);
                $table->dropColumn('grading_component_id');
            }

            // 2. Tambah kolom baru HANYA jika belum ada
            if (! Schema::hasColumn('student_grades', 'grading_item_id')) {
                $table->foreignId('grading_item_id')->after('student_id')->constrained()->onDelete('cascade');
            }

            // 3. Tambahkan Unique & Index (PostgreSQL akan error jika sudah ada, jadi kita proteksi)
            // Cara termudah: pastikan nama index-nya spesifik
        });

        // Tambahkan Index di luar closure jika ingin lebih aman
        try {
            Schema::table('student_grades', function (Blueprint $table) {
                $table->unique(['grading_item_id', 'student_id'], 'student_grades_item_student_unique');
                $table->index('student_id');
                $table->index('grading_item_id');
            });
        } catch (\Exception $e) {
            // Abaikan jika index sudah ada
        }
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropUnique('student_grades_item_student_unique');
            $table->dropIndex(['student_id']);
            $table->dropIndex(['grading_item_id']);

            $table->dropForeign(['grading_item_id']);
            $table->dropColumn('grading_item_id');

            $table->foreignId('grading_component_id')->constrained('grading_components');
        });
    }
};
