<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateCounselingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bk:migrate-counseling-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates old counseling_services data to bk_service_records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration...');

        $oldServices = \DB::table('counseling_services')->get();
        $count = 0;

        foreach ($oldServices as $oldService) {
            $type = \DB::table('counseling_types')->where('id', $oldService->counseling_type_id)->first();
            $typeName = $type ? $type->name : 'Layanan Konseling';

            $newRecordId = \DB::table('bk_service_records')->insertGetId([
                'component' => 'layanan_responsif',
                'service_type' => $typeName,
                'title' => $typeName,
                'date' => $oldService->activity_date,
                'place' => null,
                'description' => $oldService->activity_result ?? 'Tidak ada deskripsi',
                'result' => $oldService->activity_result,
                'teacher_id' => $oldService->teacher_id,
                'target_type' => 'individual',
                'document_path' => $oldService->photo_path,
                'created_at' => $oldService->created_at ?? now(),
                'updated_at' => $oldService->updated_at ?? now(),
            ]);

            \DB::table('bk_record_targets')->insert([
                'bk_service_record_id' => $newRecordId,
                'target_type' => \Modules\Akademik\Models\Student::class,
                'target_id' => $oldService->student_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $count++;
        }

        $this->info("Successfully migrated {$count} counseling records.");
    }
}
