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
            $table->integer('passing_grade')->default(70)->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_components', function (Blueprint $table) {
            $table->dropColumn('passing_grade');
        });
    }
};
