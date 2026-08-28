<?php

use Illuminate\Support\Facades\Route;
use Modules\Penilaian\Http\Controllers\PenilaianController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('penilaians', PenilaianController::class)->names('penilaian');
});
