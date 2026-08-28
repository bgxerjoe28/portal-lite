<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_agendas', function (Blueprint $table) {
            $table->unsignedInteger('start_slot')->nullable()->after('schedule_detail_id');
            $table->unsignedInteger('end_slot')->nullable()->after('start_slot');
        });

        // Backfill start_slot & end_slot for existing teaching_agendas records
        $agendas = DB::table('teaching_agendas')
            ->join('schedule_details', 'teaching_agendas.schedule_detail_id', '=', 'schedule_details.id')
            ->select('teaching_agendas.id', 'schedule_details.start_slot', 'schedule_details.end_slot')
            ->get();

        foreach ($agendas as $agenda) {
            DB::table('teaching_agendas')->where('id', $agenda->id)->update([
                'start_slot' => $agenda->start_slot,
                'end_slot' => $agenda->end_slot,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('teaching_agendas', function (Blueprint $table) {
            $table->dropColumn(['start_slot', 'end_slot']);
        });
    }
};
