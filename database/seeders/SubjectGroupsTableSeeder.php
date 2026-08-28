<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SubjectGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('subject_groups')->delete();
        
        \DB::table('subject_groups')->insert(array (
            0 => 
            array (
                'id' => 1,
            'name' => 'Kelompok A (Wajib/Umum)',
                'description' => NULL,
                'created_at' => '2025-12-15 14:37:59',
                'updated_at' => '2025-12-15 14:37:59',
            ),
            1 => 
            array (
                'id' => 2,
            'name' => 'Kelompok B (Kewilayahan)',
                'description' => NULL,
                'created_at' => '2025-12-15 14:37:59',
                'updated_at' => '2025-12-15 14:37:59',
            ),
            2 => 
            array (
                'id' => 3,
            'name' => 'Kelompok C (Peminatan)',
                'description' => NULL,
                'created_at' => '2025-12-15 14:37:59',
                'updated_at' => '2025-12-15 14:37:59',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Muatan Lokal',
                'description' => NULL,
                'created_at' => '2025-12-15 14:37:59',
                'updated_at' => '2025-12-15 14:37:59',
            ),
            4 => 
            array (
                'id' => 5,
            'name' => 'Projek Penguatan Profil Pelajar Pancasila (P5)',
                'description' => NULL,
                'created_at' => '2025-12-15 14:37:59',
                'updated_at' => '2025-12-15 14:37:59',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Bimbingan',
                'description' => NULL,
                'created_at' => '2025-12-15 14:37:59',
                'updated_at' => '2025-12-15 14:37:59',
            ),
        ));
        
        
    }
}