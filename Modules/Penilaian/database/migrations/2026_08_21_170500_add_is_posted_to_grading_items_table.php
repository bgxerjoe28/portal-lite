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
        Schema::table('grading_items', function (Blueprint $table) {
            if (!Schema::hasColumn('grading_items', 'is_posted')) {
                $table->boolean('is_posted')->default(true)->after('date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_items', function (Blueprint $table) {
            if (Schema::hasColumn('grading_items', 'is_posted')) {
                $table->dropColumn('is_posted');
            }
        });
    }
};
