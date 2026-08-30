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
        Schema::table('cbt_exams', function (Blueprint $table) {
            if (Schema::hasTable('grading_components')) {
                $table->foreignId('grading_component_id')->nullable()->after('is_active')->constrained('grading_components')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('grading_component_id')->nullable()->after('is_active');
            }

            if (Schema::hasTable('grading_items')) {
                $table->foreignId('grading_item_id')->nullable()->after('grading_component_id')->constrained('grading_items')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('grading_item_id')->nullable()->after('grading_component_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table) {
            if (Schema::hasTable('grading_components')) {
                $table->dropForeign(['grading_component_id']);
            }
            if (Schema::hasTable('grading_items')) {
                $table->dropForeign(['grading_item_id']);
            }
            $table->dropColumn(['grading_component_id', 'grading_item_id']);
        });
    }
};
