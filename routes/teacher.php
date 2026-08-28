<?php
use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\Guru\GuruAgendaController;
use Modules\Akademik\Http\Controllers\Guru\GuruCPController;
use Modules\Akademik\Http\Controllers\Guru\GuruDashboardController;
use Modules\Akademik\Http\Controllers\Guru\GuruTPController;
use Modules\Akademik\Http\Controllers\Guru\LateStudentController;
use Modules\Akademik\Http\Controllers\Guru\StudentPermitController;
use Modules\Akademik\Http\Controllers\Guru\TeachingScheduleController;
use Modules\Akademik\Http\Controllers\AgendaReportController;
use Modules\Akademik\Http\Controllers\CurriculumDashboardController;
use Modules\Akademik\Http\Controllers\Guru\GuruAttendanceRecapController;
/*
|--------------------------------------------------------------------------
| GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [GuruDashboardController::class, 'index'])
    ->name('dashboardguru');

Route::get('/curriculum/dashboard', [CurriculumDashboardController::class, 'index'])->name('curriculum.dashboard');

Route::get('/my-schedules', [TeachingScheduleController::class, 'index'])
    ->name('teaching-schedules.index');

Route::get('/my-schedules/{id}/edit', [TeachingScheduleController::class, 'edit'])
    ->name('teaching-schedules.edit');

Route::put('/my-schedules/{id}', [TeachingScheduleController::class, 'update'])
    ->name('teaching-schedules.update');

// --- 1. CP GURU ---
Route::get('/cp', [GuruCPController::class, 'index'])->name('cp.index');
Route::get('/cp/template', [GuruCPController::class, 'exportTemplate'])->name('cp.template');
Route::post('/cp', [GuruCPController::class, 'store'])->name('cp.store');
Route::post('/cp/import', [GuruCPController::class, 'import'])->name('cp.import');
Route::put('/cp/{cp}', [GuruCPController::class, 'update'])->name('cp.update');

// --- 2. TP GURU ---
// Letakkan rute statis TP di atas rute berparameter jika ada
Route::get('/cp/{cp}/tp', [GuruTPController::class, 'index'])->name('tp.index');
Route::get('/cp/{cp}/tp/template', [GuruTPController::class, 'template'])->name('tp.template');
Route::post('/cp/{cp}/tp', [GuruTPController::class, 'store'])->name('tp.store');
Route::post('/cp/{cp}/tp/import', [GuruTPController::class, 'import'])->name('tp.import');
Route::put('/tp/{tp}', [GuruTPController::class, 'update'])->name('tp.update');
Route::put('/tp/{tp}/toggle', [GuruTPController::class, 'toggle'])->name('tp.toggle');

// --- 3. LAPORAN (REPORTS) ---
// Letakkan di atas Agenda agar tidak bertabrakan
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/personal', [AgendaReportController::class, 'personal'])->name('personal');
    Route::get('/classroom', [AgendaReportController::class, 'classroom'])->name('classroom');
    Route::get('/personal/pdf', [AgendaReportController::class, 'personalPdf'])->name('personal.pdf');
    Route::get('/classroom/pdf', [AgendaReportController::class, 'classroomPdf'])->name('classroom.pdf');
});

// --- 3.1 REKAP PRESENSI MATA PELAJARAN (PER SISWA) ---
Route::prefix('attendance-recap')->name('attendance-recap.')->group(function () {
    Route::get('/', [GuruAttendanceRecapController::class, 'index'])->name('index');
    Route::get('/pdf', [GuruAttendanceRecapController::class, 'exportPdf'])->name('pdf');
    Route::get('/excel', [GuruAttendanceRecapController::class, 'exportExcel'])->name('excel');
});

// --- 4. HELPER API (UNTUK DEPENDENT DROPDOWN / FILTER FASE) ---
// Sangat penting: Letakkan di ATAS rute berparameter {agenda}
Route::prefix('agenda/api')->name('agenda.')->group(function () {
    Route::get('/schedules-by-date', [GuruAgendaController::class, 'schedulesByDate'])->name('schedules-by-date');
    Route::get('/students/{schedule}', [GuruAgendaController::class, 'studentsBySchedule'])->name('students');
    Route::get('/tp-by-schedule/{schedule}', [GuruAgendaController::class, 'tpBySchedule'])->name('tp-by-schedule');
});

// --- 5. AGENDA GURU (CRUD UTAMA) ---
// Rute Statis
Route::get('/agenda', [GuruAgendaController::class, 'index'])->name('agenda.index');
Route::get('/agenda/create', [GuruAgendaController::class, 'create'])->name('agenda.create');
Route::post('/agenda', [GuruAgendaController::class, 'store'])->name('agenda.store');

// Rute Berparameter (Wildcard) - Wajib paling bawah di grupnya
Route::get('/agenda/{agenda}', [GuruAgendaController::class, 'show'])->name('agenda.show');
Route::get('/agenda/{agenda}/edit', [GuruAgendaController::class, 'edit'])->name('agenda.edit');
Route::put('/agenda/{agenda}', [GuruAgendaController::class, 'update'])->name('agenda.update');
Route::put('/agenda/{agenda}/presensi', [GuruAgendaController::class, 'updatePresensi'])->name('agenda.presensi.update');

// --- 6. PENUGASAN MATA PELAJARAN (PR / HOMEWORK) ---
use Modules\Penugasan\Http\Controllers\TeacherAssignmentController;
Route::prefix('assignments')->name('assignments.')->group(function () {
    Route::get('/', [TeacherAssignmentController::class, 'index'])->name('index');
    Route::get('/create', [TeacherAssignmentController::class, 'create'])->name('create');
    Route::post('/', [TeacherAssignmentController::class, 'store'])->name('store');
    Route::post('/upload-image', [TeacherAssignmentController::class, 'uploadImage'])->name('upload_image');
    Route::get('/{id}/edit', [TeacherAssignmentController::class, 'edit'])->name('edit');
    Route::get('/{id}/duplicate', [TeacherAssignmentController::class, 'duplicate'])->name('duplicate');
    Route::put('/{id}', [TeacherAssignmentController::class, 'update'])->name('update');
    Route::post('/{id}/publish', [TeacherAssignmentController::class, 'publish'])->name('publish');
    Route::post('/{id}/publish-grades', [TeacherAssignmentController::class, 'publishGrades'])->name('publish_grades');
    Route::get('/{id}/performance-grading', [TeacherAssignmentController::class, 'performanceGrading'])->name('performance_grading');
    Route::post('/{id}/performance-grading', [TeacherAssignmentController::class, 'storePerformanceGrading'])->name('store_performance_grading');
    Route::delete('/{id}', [TeacherAssignmentController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/submissions', [TeacherAssignmentController::class, 'submissions'])->name('submissions');
    Route::post('/{id}/submissions/{submissionId}/grade', [TeacherAssignmentController::class, 'gradeSubmission'])->name('submissions.grade');
    Route::post('/{id}/submissions/{submissionId}/toggle-edit', [TeacherAssignmentController::class, 'toggleEditSubmission'])->name('submissions.toggle_edit');
    Route::post('/{id}/submissions/{submissionId}/reject', [TeacherAssignmentController::class, 'rejectSubmission'])->name('submissions.reject');
    Route::post('/{id}/submissions/{submissionId}/force-submit', [TeacherAssignmentController::class, 'forceSubmitSubmission'])->name('submissions.force_submit');
    Route::post('/{id}/submissions/force-submit-all', [TeacherAssignmentController::class, 'forceSubmitAll'])->name('submissions.force_submit_all');
    Route::post('/{id}/submissions/unlock-all-edit', [TeacherAssignmentController::class, 'unlockAllEdit'])->name('submissions.unlock_all_edit');
});

// --- 7. VERIFIKASI PEMINJAMAN SARPRAS (PEMBINA ESKUL / GURU) ---
Route::prefix('sarpras-approvals')->name('sarpras.approvals.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Teacher\TeacherFacilityApprovalController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\Teacher\TeacherFacilityApprovalController::class, 'show'])->name('show');
    Route::post('/{id}/approve', [\App\Http\Controllers\Teacher\TeacherFacilityApprovalController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [\App\Http\Controllers\Teacher\TeacherFacilityApprovalController::class, 'reject'])->name('reject');
});

// --- 8. PEMINJAMAN SARPRAS OLEH GURU (RUANGAN & ASET PELAJARAN / ACARA) ---
Route::prefix('sarpras-reservations')->name('sarpras.reservations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Teacher\TeacherFacilityReservationController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Teacher\TeacherFacilityReservationController::class, 'create'])->name('create');
    Route::post('/check-availability', [\App\Http\Controllers\Teacher\TeacherFacilityReservationController::class, 'checkAvailability'])->name('check-availability');
    Route::post('/', [\App\Http\Controllers\Teacher\TeacherFacilityReservationController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Teacher\TeacherFacilityReservationController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [\App\Http\Controllers\Teacher\TeacherFacilityReservationController::class, 'cancel'])->name('cancel');
});







