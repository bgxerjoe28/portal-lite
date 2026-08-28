<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hapus log aktivitas yang sudah lewat dari 90 hari (Pruning)
Schedule::command('model:prune', [
    '--model' => [\App\Models\ActivityLog::class],
])->daily()->runInBackground();

