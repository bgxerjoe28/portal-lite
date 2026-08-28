<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReligionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('religions')->delete();
        
        DB::table('religions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Islam',
                'created_at' => '2025-12-24 03:22:06',
                'updated_at' => '2025-12-24 03:22:06',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Protestan',
                'created_at' => '2025-12-24 03:22:06',
                'updated_at' => '2025-12-24 03:22:06',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Katolik',
                'created_at' => '2025-12-24 03:22:06',
                'updated_at' => '2025-12-24 03:22:06',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Hindu',
                'created_at' => '2025-12-24 03:22:06',
                'updated_at' => '2025-12-24 03:22:06',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Buddha',
                'created_at' => '2025-12-24 03:22:06',
                'updated_at' => '2025-12-24 03:22:06',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Konghucu',
                'created_at' => '2025-12-24 03:22:06',
                'updated_at' => '2025-12-24 03:22:06',
            ),
        ));
        
        
    }
}