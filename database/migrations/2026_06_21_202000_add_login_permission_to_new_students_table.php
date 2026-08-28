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
            $table->boolean('is_allowed_login')->default(false);
            $table->foreignId('allowed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('new_students', function (Blueprint $table) {
            $table->dropForeign(['allowed_by_user_id']);
            $table->dropColumn(['is_allowed_login', 'allowed_by_user_id']);
        });
    }
};
