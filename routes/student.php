<?php

use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;
use Modules\Kesiswaan\Http\Controllers\AttendanceController;
use Modules\Akademik\Http\Controllers\StudentProfileController;

// Rute khusus Siswa

Route::get('/dashboard', [StudentController::class, 'index'])->name('dashboard');
Route::post('/biodata', [StudentController::class, 'updateBiodata'])->name('biodata.update');

Route::get('/profile', [StudentProfileController::class, 'showForm'])->name('profile');
Route::post('/profile', [StudentProfileController::class, 'submitForm'])->name('profile.submit');
Route::post('/profile/draft', [StudentProfileController::class, 'saveDraft'])->name('profile.draft');

// Penugasan Mata Pelajaran
use Modules\Penugasan\Http\Controllers\StudentAssignmentController;
Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
Route::get('/assignments/{id}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
Route::post('/assignments/{id}/draft', [StudentAssignmentController::class, 'saveDraft'])->name('assignments.draft');
Route::post('/assignments/{id}/submit', [StudentAssignmentController::class, 'submit'])->name('assignments.submit');

