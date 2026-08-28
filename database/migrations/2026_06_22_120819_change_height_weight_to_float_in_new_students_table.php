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
        Schema::table('new_students', function (Blueprint $table) {
            $table->float('height')->nullable()->change();
            $table->float('weight')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('new_students', function (Blueprint $table) {
            $table->integer('height')->nullable()->change();
            $table->integer('weight')->nullable()->change();
        });
    }
};
