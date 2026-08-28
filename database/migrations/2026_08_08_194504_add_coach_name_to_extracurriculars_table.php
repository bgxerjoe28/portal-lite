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
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->string('coach_name')->nullable()->after('teacher_id');
        });

        Schema::table('extracurricular_sessions', function (Blueprint $table) {
            $table->dropColumn('coach_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropColumn('coach_name');
        });

        Schema::table('extracurricular_sessions', function (Blueprint $table) {
            $table->string('coach_name')->nullable()->after('title');
        });
    }
};
