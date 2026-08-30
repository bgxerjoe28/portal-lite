<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('new_students')) {
            Schema::table('new_students', function (Blueprint $table) {
                $table->string('jalur')->nullable();
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
                $table->dropColumn('jalur');
            });
        }
    }
};
