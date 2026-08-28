<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. BERSIHKAN TABEL MASTER MAPEL
        Schema::table('subjects', function (Blueprint $table) {
            // Kita hapus kolom group/subject_group_id karena akan dipindah ke tabel mapping
            if (Schema::hasColumn('subjects', 'subject_group_id')) {
                $table->dropForeign(['subject_group_id']);
                $table->dropColumn('subject_group_id');
            }
            if (Schema::hasColumn('subjects', 'group')) {
                $table->dropColumn('group');
            }
            // Hasil akhir tabel subjects hanya: id, code, name, timestamps, softDeletes
        });

        // 2. BUAT TABEL MAPPING (ATURAN MAPEL)
        Schema::create('subject_mappings', function (Blueprint $table) {
            $table->id();

            // Mapel apa?
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            // Untuk Tingkat berapa? (10, 11, 12)
            $table->tinyInteger('level'); // 10, 11, 12

            // Masuk Kelompok apa di tingkat itu? (Wajib/Peminatan/Mulok)
            $table->unsignedTinyInteger('subject_group_id');
            $table->foreign('subject_group_id')->references('id')->on('subject_groups');

            $table->timestamps();

            // Mencegah duplikasi: Mapel INF di Kelas 10 hanya boleh ada 1 aturan
            $table->unique(['subject_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_mappings');
        // Rollback subjects table logic (optional/skipped for brevity)
    }
};
