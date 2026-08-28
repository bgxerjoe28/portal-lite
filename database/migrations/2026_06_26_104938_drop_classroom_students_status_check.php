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
        Schema::table('classroom_students', function (Blueprint $table) {
            DB::statement('ALTER TABLE classroom_students DROP CONSTRAINT IF EXISTS classroom_students_status_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_students', function (Blueprint $table) {
            DB::statement("ALTER TABLE classroom_students ADD CONSTRAINT classroom_students_status_check CHECK (status::text = ANY (ARRAY['aktif'::character varying, 'pindah'::character varying, 'keluar'::character varying, 'lulus'::character varying]::text[]))");
        });
    }
};
