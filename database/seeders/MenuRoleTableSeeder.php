<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class MenuRoleTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('menu_role')->delete();
        
        DB::table('menu_role')->insert(array (
            0 => 
            array (
                'menu_id' => 1,
                'role_id' => 1,
            ),
            1 => 
            array (
                'menu_id' => 2,
                'role_id' => 2,
            ),
            2 => 
            array (
                'menu_id' => 3,
                'role_id' => 1,
            ),
            3 => 
            array (
                'menu_id' => 4,
                'role_id' => 1,
            ),
            4 => 
            array (
                'menu_id' => 5,
                'role_id' => 1,
            ),
            5 => 
            array (
                'menu_id' => 6,
                'role_id' => 1,
            ),
            6 => 
            array (
                'menu_id' => 6,
                'role_id' => 2,
            ),
            7 => 
            array (
                'menu_id' => 7,
                'role_id' => 2,
            ),
            8 => 
            array (
                'menu_id' => 9,
                'role_id' => 1,
            ),
            9 => 
            array (
                'menu_id' => 10,
                'role_id' => 1,
            ),
            10 => 
            array (
                'menu_id' => 11,
                'role_id' => 1,
            ),
            11 => 
            array (
                'menu_id' => 13,
                'role_id' => 1,
            ),
            12 => 
            array (
                'menu_id' => 14,
                'role_id' => 1,
            ),
            13 => 
            array (
                'menu_id' => 15,
                'role_id' => 1,
            ),
            14 => 
            array (
                'menu_id' => 16,
                'role_id' => 1,
            ),
            15 => 
            array (
                'menu_id' => 17,
                'role_id' => 1,
            ),
            16 => 
            array (
                'menu_id' => 18,
                'role_id' => 1,
            ),
            17 => 
            array (
                'menu_id' => 19,
                'role_id' => 1,
            ),
            18 => 
            array (
                'menu_id' => 20,
                'role_id' => 1,
            ),
            19 => 
            array (
                'menu_id' => 21,
                'role_id' => 1,
            ),
            20 => 
            array (
                'menu_id' => 22,
                'role_id' => 2,
            ),
            21 => 
            array (
                'menu_id' => 23,
                'role_id' => 2,
            ),
            22 => 
            array (
                'menu_id' => 24,
                'role_id' => 1,
            ),
            23 => 
            array (
                'menu_id' => 24,
                'role_id' => 4,
            ),
        ));
        
        
    }
}