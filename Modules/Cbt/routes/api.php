<?php

use Illuminate\Support\Facades\Route;
use Modules\Cbt\Http\Controllers\CbtController;
use Modules\Cbt\Http\Controllers\CbtIrtCallbackController;

// Internal API / Webhook Callback dari Python IRT Microservice
Route::post('cbt/internal/irt-callback', [CbtIrtCallbackController::class, 'handleCallback'])
    ->name('cbt.internal.irt_callback');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cbts', CbtController::class)->names('cbt');
});

