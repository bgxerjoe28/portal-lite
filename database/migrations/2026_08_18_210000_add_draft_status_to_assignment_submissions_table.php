<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. In PostgreSQL, drop the enum check constraint if exists
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE assignment_submissions DROP CONSTRAINT IF EXISTS assignment_submissions_status_check');
        }

        // 2. Modify status and submitted_at columns
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
            $table->dateTime('submitted_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->string('status')->default('submitted')->change();
        });
    }
};
