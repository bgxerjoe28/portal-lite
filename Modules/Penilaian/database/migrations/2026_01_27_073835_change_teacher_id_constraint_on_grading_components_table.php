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
        Schema::table('grading_components', function (Blueprint $table) {
            // 1. Hapus foreign key lama yang merujuk ke 'users'
            // Laravel biasanya memberi nama: nama_tabel_nama_kolom_foreign
            $table->dropForeign(['teacher_id']);

            // 2. Buat foreign key baru yang merujuk ke 'teachers'
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_components', function (Blueprint $table) {
            // Kembalikan ke 'users' jika migrasi di-rollback
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
