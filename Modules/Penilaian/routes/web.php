<?php

use Illuminate\Support\Facades\Route;
use Modules\Penilaian\Http\Controllers\PenilaianController;
use Modules\Penilaian\Http\Controllers\GradeController;
use Modules\Penilaian\Http\Controllers\GradingComponentController;

/*
|--------------------------------------------------------------------------
| MODUL PENILAIAN
|--------------------------------------------------------------------------
*/
Route::prefix('penilaian')->name('penilaian.')->group(function () {

    Route::resource('components', GradingComponentController::class);
    Route::post('grades/{id}/toggle-post', [GradeController::class, 'togglePost'])->name('grades.toggle_post');
    Route::get('grades/export', [GradeController::class, 'exportExcel'])->name('grades.export');
    Route::get('grades/recap', [GradeController::class, 'recap'])->name('grades.recap');
    Route::resource('grades', GradeController::class);
});