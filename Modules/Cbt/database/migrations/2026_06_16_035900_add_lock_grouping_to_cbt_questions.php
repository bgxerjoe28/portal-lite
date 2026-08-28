<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_questions', function (Blueprint $table) {
            // Lock N: jika true, soal ini tidak ikut diacak saat shuffle
            $table->boolean('lock_n')->default(false)->after('score');
            // Grouping: nomor grup agar soal-soal dalam grup yang sama tetap berurutan saat shuffle
            // Contoh: soal paragraf panjang + 4 soal turunannya semua diberi grouping yang sama
            $table->string('grouping')->nullable()->after('lock_n');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->dropColumn(['lock_n', 'grouping']);
        });
    }
};
