<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Akademik\Models\SubjectGroup;

// Model Pivot

class AkademikDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Kelompok A (Wajib/Umum)',
            'Kelompok B (Kewilayahan)',
            'Kelompok C (Peminatan)',
            'Muatan Lokal',
            'Projek Penguatan Profil Pelajar Pancasila (P5)',
        ];

        foreach ($groups as $g) {
            SubjectGroup::firstOrCreate(['name' => $g]);
        }
    }
}
