<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\AkademikController;
use Modules\Akademik\Http\Controllers\Guru\GuruCPController;
use Modules\Akademik\Http\Controllers\Guru\GuruTPController;
use Modules\Akademik\Http\Controllers\AgendaReportController;
use Modules\Akademik\Http\Controllers\Guru\GuruAgendaController;
use Modules\Akademik\Http\Controllers\LearningObjectiveTPController;
use Modules\Akademik\Http\Controllers\LearningOutcomeCPController;
use Modules\Akademik\Http\Controllers\Guru\LateStudentController;
use Modules\Akademik\Http\Controllers\Guru\StudentPermitController;
use Modules\Akademik\Http\Controllers\AcademicCalendarController;

Route::middleware(['auth'])->group(function () {
    Route::resource('akademiks', AkademikController::class)->names('akademik');
});
/*
|--------------------------------------------------------------------------
| CP / TP MANAGEMENT (ADMIN & GURU)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role_or_permission:admin|guru|manage-akademik|access-akademik'])
    ->prefix('akademik/cp')
    ->name('akademik.')
    ->group(function () {
        Route::get('/', [LearningOutcomeCPController::class, 'index'])->name('cp.index');
        Route::post('/', [LearningOutcomeCPController::class, 'store'])->name('cp.store');
        Route::put('/{cp}', [LearningOutcomeCPController::class, 'update'])->name('cp.update');
        Route::delete('/{cp}', [LearningOutcomeCPController::class, 'destroy'])->name('cp.destroy');

        Route::post('/import', [LearningOutcomeCPController::class, 'import'])->name('cp.import');
        Route::get('/template', [LearningOutcomeCPController::class, 'template'])->name('cp.export');
        Route::put('/restore/{id}', [LearningOutcomeCPController::class, 'restore'])->name('cp.restore');

    });
Route::middleware(['auth', 'role_or_permission:admin|manage-akademik|access-akademik'])
    ->prefix('akademik')
    ->group(function () {

        // Rollover Tahun Ajaran
        Route::post('/academic-years/rollover', [\Modules\Akademik\Http\Controllers\AcademicYearRolloverController::class, 'processRollover'])
            ->name('akademik.academic-years.rollover');
        Route::get('/academic-years/rollover/candidates', [\Modules\Akademik\Http\Controllers\AcademicYearRolloverController::class, 'getCandidates'])
            ->name('akademik.academic-years.candidates');

        Route::get('/cp/{cp}/tp', [LearningObjectiveTPController::class, 'index'])
            ->name('akademik.tp.index');
        Route::post('/cp/{cp}/tp', [LearningObjectiveTPController::class, 'store'])
            ->name('akademik.tp.store');
        Route::put('/tp/{tp}', [LearningObjectiveTPController::class, 'update'])
            ->name('akademik.tp.update');
        Route::delete('/tp/{tp}', [LearningObjectiveTPController::class, 'destroy'])
            ->name('akademik.tp.destroy');
        Route::put('/tp/{id}/restore', [LearningObjectiveTPController::class, 'restore'])
            ->name('akademik.tp.restore');
        Route::post('/cp/{cp}/tp/import', [LearningObjectiveTPController::class, 'import'])
            ->name('akademik.tp.import');
        Route::get('/cp/{cp}/tp/template', [LearningObjectiveTPController::class, 'exportTemplate'])
            ->name('akademik.tp.template');
    });

/*
|--------------------------------------------------------------------------
| Ijin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role_or_permission:guru|admin|guru bk|pegawai|manage-permits'])->prefix('permits')->name('permits.')->group(function () {
    Route::get('/', [StudentPermitController::class, 'index'])->name('index');
    Route::post('/', [StudentPermitController::class, 'store'])->name('store');
    Route::post('/bulk', [StudentPermitController::class, 'storeBulk'])->name('store-bulk');
    Route::delete('/{permit}', [StudentPermitController::class, 'destroy'])->name('destroy');
    Route::get('/trash', [StudentPermitController::class, 'trash'])->name('trash');
    Route::get('/frequency', [StudentPermitController::class, 'frequency'])->name('frequency');
    Route::post('/{id}/restore', [StudentPermitController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force-delete', [StudentPermitController::class, 'forceDelete'])->name('force-delete');
    Route::post('/trash/empty', [StudentPermitController::class, 'emptyTrash'])->name('empty-trash');
    Route::get('/export-excel', [StudentPermitController::class, 'exportExcel'])->name('export-excel');
    Route::get('/export-pdf', [StudentPermitController::class, 'exportPdf'])->name('export-pdf');
    Route::post('/sync-google-sheet', [StudentPermitController::class, 'syncGoogleSheet'])->name('sync-google-sheet');

    // API untuk mencari siswa secara cepat (Autocomplete) guru.permits.search-students
    Route::get('/search-students', [StudentPermitController::class, 'searchStudents'])->name('search-students');
});
/*
|--------------------------------------------------------------------------
| Siswa Terlambat
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role_or_permission:guru|admin|guru bk|pegawai|manage-lates'])->prefix('lates')->name('lates.')->group(function () {
    Route::get('/', [LateStudentController::class, 'index'])->name('index');
    Route::get('/search', [LateStudentController::class, 'searchStudents'])->name('search');
    Route::get('/print/{permit}', [LateStudentController::class, 'print'])->name('print');
    Route::get('/pdf/{permit}', [LateStudentController::class, 'downloadPdf'])->name('pdf');
    Route::post('/', [LateStudentController::class, 'store'])->name('store');
    Route::delete('/{permit}', [LateStudentController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| KALENDER AKADEMIK (VISUAL – ADMIN & GURU)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role_or_permission:admin|guru|manage-akademik|access-akademik'])
    ->prefix('calendar')
    ->name('calendar.')
    ->group(function () {
        Route::get('/view', [AcademicCalendarController::class, 'view'])
            ->name('view');
        Route::get('/print', [AcademicCalendarController::class, 'print'])
            ->name('print');
    });

/*
|--------------------------------------------------------------------------
| TKA (Tes Kompetensi Akademik)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role_or_permission:admin|guru|manage-akademik|access-akademik'])
    ->prefix('akademik')
    ->name('akademik.')
    ->group(function () {
        Route::get('tka-recap', [\Modules\Akademik\Http\Controllers\TkaStudentController::class, 'recap'])->name('tka-recap.index');
    });

Route::middleware(['auth', 'role_or_permission:admin|manage-akademik|access-akademik'])
    ->prefix('akademik')
    ->name('akademik.')
    ->group(function () {
        Route::post('tka-subjects/toggle-registration', [\Modules\Akademik\Http\Controllers\TkaSubjectController::class, 'toggleRegistration'])->name('tka-subjects.toggle-registration');
        Route::resource('tka-subjects', \Modules\Akademik\Http\Controllers\TkaSubjectController::class);
        
        Route::get('tka-subjects/{tka_subject}/students', [\Modules\Akademik\Http\Controllers\TkaStudentController::class, 'index'])->name('tka-subjects.students.index');
        Route::post('tka-subjects/{tka_subject}/students', [\Modules\Akademik\Http\Controllers\TkaStudentController::class, 'store'])->name('tka-subjects.students.store');
        Route::delete('tka-subjects/{tka_subject}/students/{tka_student}', [\Modules\Akademik\Http\Controllers\TkaStudentController::class, 'destroy'])->name('tka-subjects.students.destroy');
        
        Route::get('tka-subjects/{tka_subject}/sessions', [\Modules\Akademik\Http\Controllers\TkaSessionController::class, 'index'])->name('tka-subjects.sessions.index');
        Route::post('tka-subjects/{tka_subject}/sessions', [\Modules\Akademik\Http\Controllers\TkaSessionController::class, 'store'])->name('tka-subjects.sessions.store');
        Route::put('tka-sessions/{tka_session}', [\Modules\Akademik\Http\Controllers\TkaSessionController::class, 'update'])->name('tka-sessions.update');
        Route::delete('tka-sessions/{tka_session}', [\Modules\Akademik\Http\Controllers\TkaSessionController::class, 'destroy'])->name('tka-sessions.destroy');

        Route::get('tka-sessions/{tka_session}/attendances', [\Modules\Akademik\Http\Controllers\TkaAttendanceController::class, 'index'])->name('tka-sessions.attendances.index');
        Route::post('tka-sessions/{tka_session}/attendances', [\Modules\Akademik\Http\Controllers\TkaAttendanceController::class, 'store'])->name('tka-sessions.attendances.store');
    });
