<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel 'wadah' untuk item penilaian (Tugas 1, Tugas 2, dsb)
        Schema::create('grading_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_component_id')->constrained('grading_components')->onDelete('cascade');
            $table->string('title'); // Contoh: "Tugas 1", "Ulangan Harian 1"
            $table->date('date');
            $table->timestamps();
        });

        // 2. Ubah tabel student_grades agar merujuk ke 'item', bukan 'komponen' langsung
        Schema::table('student_grades', function (Blueprint $table) {
            // Hapus foreign key lama ke grading_components
            $table->dropForeign(['grading_component_id']);
            $table->dropColumn('grading_component_id');

            // Tambah foreign key baru ke grading_items
            $table->foreignId('grading_item_id')
                ->after('student_id')
                ->constrained('grading_items')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['grading_item_id']);
            $table->dropColumn('grading_item_id');
            $table->foreignId('grading_component_id')->constrained('grading_components');
        });
        
        Schema::dropIfExists('grading_items');
    }
};
