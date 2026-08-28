<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::statement('
                UPDATE academic_events
                SET start_date = date,
                    end_date = date
                WHERE date IS NOT NULL
            ');
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::statement('
                UPDATE academic_events
                SET start_date = NULL,
                    end_date = NULL
            ');
        });
    }
};
