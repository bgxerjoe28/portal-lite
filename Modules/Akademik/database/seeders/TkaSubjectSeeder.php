<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;

class TkaSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            $this->command->warn('No active academic year found. Skipping TKA Subject seeding.');
            return;
        }

        $subjects = [
            'Matematika Tingkat Lanjut',
            'Bahasa Indonesia Tingkat Lanjut',
            'Bahasa Inggris Tingkat Lanjut',
            'Fisika',
            'Kimia',
            'Biologi',
            'Pendidikan Pancasila dan Kewarganegaraan',
            'Ekonomi',
            'Geografi',
            'Sosiologi',
            'Sejarah',
            'Bahasa Jepang'
        ];

        foreach ($subjects as $index => $subjectName) {
            \Modules\Akademik\Models\TkaSubject::firstOrCreate([
                'academic_year_id' => $activeYear->id,
                'name' => $subjectName,
            ], [
                'code' => 'TKA-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }
    }
}
