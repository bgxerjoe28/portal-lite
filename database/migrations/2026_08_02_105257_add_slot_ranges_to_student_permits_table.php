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
        Schema::table('student_permits', function (Blueprint $table) {
            $table->integer('start_slot')->nullable()->after('permit_type');
            $table->integer('end_slot')->nullable()->after('start_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_permits', function (Blueprint $table) {
            $table->dropColumn(['start_slot', 'end_slot']);
        });
    }
};
