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
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('type')->default('standard')->after('title');
        });

        Schema::table('assignment_questions', function (Blueprint $table) {
            $table->string('type')->default('essay')->change();
        });

        // PostgreSQL requires explicitly dropping the constraint since we change the ENUM to a string
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE assignment_questions DROP CONSTRAINT IF EXISTS assignment_questions_type_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
