<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Hapus kolom string lama (jika ada)
            if (Schema::hasColumn('subjects', 'group')) {
                $table->dropColumn('group');
            }

            // Tambah kolom ID baru
           $table->foreignId('subject_group_id')
              ->nullable()
              ->after('name')
              ->constrained('subject_groups')
              ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
        if (Schema::hasColumn('subjects', 'subject_group_id')) {
            $table->dropConstrainedForeignId('subject_group_id');
        }

        if (!Schema::hasColumn('subjects', 'group')) {
            $table->string('group')->default('A');
        }
    });
    }
};
