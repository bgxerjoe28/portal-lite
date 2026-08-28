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
        Schema::table('cbt_room_students', function (Blueprint $table) {
            // Harus drop index yang mereferensikan kolom ini SEBELUM drop column
            // agar kompatibel dengan SQLite (dipakai saat testing)
            $table->dropUnique('cbt_room_students_student_id_cbt_session_id_unique');
            $table->dropUnique('room_session_seat_unique');
            $table->dropForeign(['cbt_session_id']);
            $table->dropColumn('cbt_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbt_room_students', function (Blueprint $table) {
            $table->foreignId('cbt_session_id')->nullable()->constrained('cbt_sessions')->cascadeOnDelete();
        });
    }
};
