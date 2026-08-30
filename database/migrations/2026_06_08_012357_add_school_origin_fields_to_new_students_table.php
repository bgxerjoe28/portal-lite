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
        if (Schema::hasTable('new_students')) {
            Schema::table('new_students', function (Blueprint $table) {
                $table->string('prev_school_type')->nullable();
                $table->string('prev_school_status')->nullable();
                $table->string('prev_school_name')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('new_students')) {
            Schema::table('new_students', function (Blueprint $table) {
                $table->dropColumn(['prev_school_type', 'prev_school_status', 'prev_school_name']);
            });
        }
    }
};
