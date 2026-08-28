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
        Schema::table('academic_years', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('semester');
            $table->date('end_date')->nullable()->after('start_date');

            // selector hari sekolah efektif
            $table->jsonb('school_days')
                ->nullable()
                ->comment('Hari sekolah efektif: mon,tue,wed,thu,fri,sat');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            if (Schema::hasColumn('academic_years', 'start_date')) {
                $table->dropColumn(['start_date', 'end_date', 'school_days']);
            }
        });
    }
};
