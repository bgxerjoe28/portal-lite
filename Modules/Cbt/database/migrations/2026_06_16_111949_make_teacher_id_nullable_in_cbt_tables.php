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
        Schema::table('cbt_banks', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
        });

        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbt_tables', function (Blueprint $table) {
            //
        });
    }
};
