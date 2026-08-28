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
        Schema::table('teaching_agendas', function (Blueprint $table) {
            // Kita buat nullable dulu jika sudah ada data lama di database
            $table->foreignId('schedule_detail_id')
                ->nullable()
                ->after('schedule_id')
                ->constrained('schedule_details')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teaching_agendas', function (Blueprint $table) {

            $table->dropForeign(['schedule_detail_id']);
            $table->dropColumn('schedule_detail_id');

        });
    }
};
