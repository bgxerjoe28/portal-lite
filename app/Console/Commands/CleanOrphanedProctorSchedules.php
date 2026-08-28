<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOrphanedProctorSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbt:clean-orphaned-proctor-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned auto-generated proctor schedules from deleted independent exams.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari jadwal pengawas ujian mandiri yang sudah terlanjur yatim...');
        
        $session = \Modules\Cbt\Models\CbtSession::where('name', 'Sesi Ujian Mandiri')->first();
        if (!$session) {
            $this->warn('Sesi Ujian Mandiri tidak ditemukan.');
            return;
        }

        $schedules = \Modules\Cbt\Models\CbtProctorSchedule::where('cbt_session_id', $session->id)->get();
        $deleted = 0;
        
        foreach($schedules as $schedule) {
            $room = \Modules\Cbt\Models\CbtRoom::find($schedule->cbt_room_id);
            if ($room && str_starts_with($room->name, 'Ruang ')) {
                $className = substr($room->name, 6);
                $classroom = \Modules\Akademik\Models\Classroom::where('name', $className)->first();
                if ($classroom) {
                    $hasExam = \Modules\Cbt\Models\CbtExam::where('is_independent', true)
                        ->where('teacher_id', $schedule->teacher_id)
                        ->whereDate('start_time', $schedule->date)
                        ->whereHas('classrooms', function($q) use ($classroom) {
                            $q->where('classrooms.id', $classroom->id);
                        })->exists();
                    
                    if (!$hasExam) {
                        $schedule->delete();
                        $deleted++;
                    }
                }
            }
        }
        
        $this->info("Berhasil menghapus {$deleted} jadwal mandiri yang terlanjur yatim.");
    }
}
