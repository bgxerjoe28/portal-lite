<?php

use Illuminate\Support\Facades\Route;
use Modules\Cbt\Http\Controllers\CbtBankController;
use Modules\Cbt\Http\Controllers\CbtExamController;
use Modules\Cbt\Http\Controllers\CbtImageController;
use Modules\Cbt\Http\Controllers\StudentCbtController;
use Modules\Cbt\Http\Controllers\CbtRoomController;
use Modules\Cbt\Http\Controllers\CbtProctorController;
use Modules\Cbt\Http\Controllers\CbtSessionController;
use Modules\Cbt\Http\Controllers\CbtProctorScheduleController;

// 0. RUTE GAMBAR SOAL — Proxy streaming dari MinIO/S3/local (bypass bucket 403)
//    Dapat diakses oleh semua pengguna terautentikasi (siswa, guru, admin)
Route::middleware(['auth'])->group(function () {
    Route::get('cbt/questions/image/{filename}', [CbtImageController::class, 'show'])
        ->name('cbt.questions.image')
        ->where('filename', '[\w\-\.]+');
});

// 1. RUTE GURU & ADMIN (Manajemen Bank Soal & Penjadwalan Ujian)
Route::middleware(['auth', 'role_or_permission:admin|guru|manage-cbt|access-cbt'])->group(function () {
    // Upload Gambar Soal CBT (Editor)
    Route::post('cbt/questions/upload-image', [CbtImageController::class, 'upload'])->name('cbt.questions.upload_image');

    // Bank Soal
    Route::get('cbt/bank/download-template', [CbtBankController::class, 'downloadTemplate'])->name('cbt.bank.template');
    Route::post('cbt/bank/{id}/import', [CbtBankController::class, 'importExcel'])->name('cbt.bank.import');
    Route::post('cbt/bank/{id}/import-keys', [CbtBankController::class, 'importKeysOnly'])->name('cbt.bank.import_keys');
    Route::post('cbt/bank/{id}/clear', [CbtBankController::class, 'clearQuestions'])->name('cbt.bank.clear');
    Route::put('cbt/bank/{bank_id}/questions/{question_id}/key', [CbtBankController::class, 'updateQuestionKey'])->name('cbt.bank.questions.update_key');
    Route::put('cbt/bank/{bank_id}/questions/{question_id}/patch', [CbtBankController::class, 'patchQuestion'])->name('cbt.bank.questions.patch');
    Route::get('cbt/bank/{id}/questions', [CbtBankController::class, 'questions'])->name('cbt.bank.questions');
    Route::get('cbt/bank/{id}/analytics', [CbtBankController::class, 'analytics'])->name('cbt.bank.analytics');
    Route::post('cbt/bank/{id}/recalculate-analytics', [CbtBankController::class, 'recalculateAnalytics'])->name('cbt.bank.recalculate_analytics');
    Route::get('cbt/bank/{id}/export-full-report-pdf', [CbtBankController::class, 'exportFullReportPdf'])->name('cbt.bank.export_full_report_pdf');
    Route::get('cbt/bank/{id}/export-answers-pdf', [CbtBankController::class, 'exportAnswersPdf'])->name('cbt.bank.export_answers_pdf');
    Route::get('cbt/bank/{id}/export-dichotomous-pdf', [CbtBankController::class, 'exportDichotomousPdf'])->name('cbt.bank.export_dichotomous_pdf');
    Route::get('cbt/bank/{id}/export-daftar-nilai-pdf', [CbtBankController::class, 'exportDaftarNilaiPdf'])->name('cbt.bank.export_daftar_nilai_pdf');
    Route::resource('cbt/bank', CbtBankController::class)->names([
        'index' => 'cbt.bank.index',
        'create' => 'cbt.bank.create',
        'store' => 'cbt.bank.store',
        'show' => 'cbt.bank.show',
        'edit' => 'cbt.bank.edit',
        'update' => 'cbt.bank.update',
        'destroy' => 'cbt.bank.destroy',
    ]);

    // Ruang Tes & Seating CRUD
    Route::get('cbt/rooms/download-template', [CbtRoomController::class, 'downloadSeatingTemplate'])->name('cbt.rooms.download-template');
    Route::post('cbt/rooms/{id}/import-seating', [CbtRoomController::class, 'importSeating'])->name('cbt.rooms.import-seating');
    Route::post('cbt/rooms/{id}/assign-seat', [CbtRoomController::class, 'assignSeat'])->name('cbt.rooms.assign-seat');
    Route::post('cbt/rooms/{id}/clear-seat/{seat_number}', [CbtRoomController::class, 'clearSeat'])->name('cbt.rooms.clear-seat');
    Route::get('cbt/rooms/{id}/seating', [CbtRoomController::class, 'seating'])->name('cbt.rooms.seating');
    Route::resource('cbt/rooms', CbtRoomController::class)->names([
        'index' => 'cbt.rooms.index',
        'create' => 'cbt.rooms.create',
        'store' => 'cbt.rooms.store',
        'show' => 'cbt.rooms.show',
        'edit' => 'cbt.rooms.edit',
        'update' => 'cbt.rooms.update',
        'destroy' => 'cbt.rooms.destroy',
    ]);

    // Jadwal Ujian & Proktoring
    Route::resource('cbt/sessions', CbtSessionController::class)->names([
        'index' => 'cbt.sessions.index',
        'store' => 'cbt.sessions.store',
        'update' => 'cbt.sessions.update',
        'destroy' => 'cbt.sessions.destroy',
    ]);

    Route::resource('cbt/proctor-schedules', CbtProctorScheduleController::class)->names([
        'index' => 'cbt.proctor-schedules.index',
        'store' => 'cbt.proctor-schedules.store',
        'update' => 'cbt.proctor-schedules.update',
        'destroy' => 'cbt.proctor-schedules.destroy',
    ]);
    Route::get('cbt/exams/{id}/results/export', [CbtExamController::class, 'exportResults'])->name('cbt.exams.results.export');
    Route::get('cbt/exams/{id}/results/recap', [CbtExamController::class, 'recap'])->name('cbt.exams.results.recap');
    Route::get('cbt/exams/{id}/results', [CbtExamController::class, 'results'])->name('cbt.exams.results');
    Route::post('cbt/exams/{id}/results/reset/{student_exam_id}', [CbtExamController::class, 'resetStudentExam'])->name('cbt.exams.results.reset');
    Route::post('cbt/exams/{id}/results/force-submit-all', [CbtExamController::class, 'forceSubmitAllStudentExams'])->name('cbt.exams.results.force-submit-all');
    Route::post('cbt/exams/{id}/results/force-submit/{student_exam_id}', [CbtExamController::class, 'forceSubmitStudentExam'])->name('cbt.exams.results.force-submit');
    Route::post('cbt/exams/{id}/results/allow-reenter/{student_exam_id}', [CbtExamController::class, 'allowReenterStudent'])->name('cbt.exams.results.allow-reenter');
    Route::post('cbt/exams/{id}/results/reopen/{student_exam_id}', [CbtExamController::class, 'reopenStudentExam'])->name('cbt.exams.results.reopen');
    Route::post('cbt/exams/{id}/recalculate-analytics', [CbtExamController::class, 'recalculateAnalytics'])->name('cbt.exams.recalculate_analytics');
    Route::get('cbt/exams/{id}/export-full-report-pdf', [CbtExamController::class, 'exportFullReportPdf'])->name('cbt.exams.export_full_report_pdf');
    Route::get('cbt/exams/{id}/export-berita-acara-pdf', [CbtExamController::class, 'exportBeritaAcaraPdf'])->name('cbt.exams.export_berita_acara_pdf');
    Route::get('cbt/exams/{id}/export-answers-pdf', [CbtExamController::class, 'exportAnswersPdf'])->name('cbt.exams.export_answers_pdf');
    Route::get('cbt/exams/{id}/export-dichotomous-pdf', [CbtExamController::class, 'exportDichotomousPdf'])->name('cbt.exams.export_dichotomous_pdf');
    Route::get('cbt/exams/{id}/export-daftar-nilai-pdf', [CbtExamController::class, 'exportDaftarNilaiPdf'])->name('cbt.exams.export_daftar_nilai_pdf');
    Route::get('cbt/exams/{id}/dry-run', [CbtExamController::class, 'dryRun'])->name('cbt.exams.dry-run');
    Route::post('cbt/exams/{id}/update-notes', [CbtExamController::class, 'updateNotes'])->name('cbt.exams.update_notes');
    Route::patch('cbt/exams/{id}/toggle-independent', [CbtExamController::class, 'toggleIndependent'])->name('cbt.exams.toggle-independent');
    Route::patch('cbt/exams/{id}/toggle-active', [CbtExamController::class, 'toggleActive'])->name('cbt.exams.toggle-active');
    Route::post('cbt/exams/batch', [CbtExamController::class, 'batchStore'])->name('cbt.exams.batch');
    Route::resource('cbt/exams', CbtExamController::class)->names([
        'index' => 'cbt.exams.index',
        'create' => 'cbt.exams.create',
        'store' => 'cbt.exams.store',
        'show' => 'cbt.exams.show',
        'edit' => 'cbt.exams.edit',
        'update' => 'cbt.exams.update',
        'destroy' => 'cbt.exams.destroy',
    ]);

    // Proktoring / Pengawas Console
    Route::get('cbt/proctor', [CbtProctorController::class, 'index'])->name('cbt.proctor.index');
    Route::get('cbt/proctor/exam-room/{id}', [CbtProctorController::class, 'show'])->name('cbt.proctor.show');
    Route::post('cbt/proctor/exam-room/{id}/generate-token', [CbtProctorController::class, 'generateToken'])->name('cbt.proctor.generate-token');
    Route::post('cbt/proctor/exam-room/{id}/start', [CbtProctorController::class, 'startExam'])->name('cbt.proctor.start');
    Route::post('cbt/proctor/exam-room/{id}/attendance', [CbtProctorController::class, 'saveAttendance'])->name('cbt.proctor.attendance');
    Route::post('cbt/proctor/exam-room/{id}/end', [CbtProctorController::class, 'endExam'])->name('cbt.proctor.end');
    Route::post('cbt/proctor/exam-room/{id}/restart', [CbtProctorController::class, 'restartRoomSession'])->name('cbt.proctor.restart-room');
    Route::post('cbt/proctor/exam-room/{id}/logout-student/{student_id}', [CbtProctorController::class, 'forceLogoutStudent'])->name('cbt.proctor.logout-student');
    Route::post('cbt/proctor/exam-room/{id}/reopen-student/{student_id}', [CbtProctorController::class, 'reopenStudentExam'])->name('cbt.proctor.reopen-student');
    Route::get('cbt/proctor/exam-room/{id}/status', [CbtProctorController::class, 'getStatus'])->name('cbt.proctor.status');
});

// 2. RUTE SISWA (Pengerjaan Ujian)
Route::middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('student/cbt', [StudentCbtController::class, 'index'])->name('student.cbt.index');
    Route::get('student/cbt/exam/{cbt_exam_id}', [StudentCbtController::class, 'showExam'])->name('student.cbt.exam');
    Route::post('student/cbt/exam/{cbt_exam_id}/verify-token', [StudentCbtController::class, 'verifyToken'])->name('student.cbt.verify-token');
    Route::post('student/cbt/exam/{cbt_exam_id}/save-answer', [StudentCbtController::class, 'saveAnswer'])->name('student.cbt.save-answer');
    Route::post('student/cbt/exam/{cbt_exam_id}/submit', [StudentCbtController::class, 'submitExam'])->name('student.cbt.submit');
    Route::post('student/cbt/exam/{cbt_exam_id}/heartbeat', [StudentCbtController::class, 'heartbeat'])->name('student.cbt.heartbeat');
    Route::post('student/cbt/exam/{cbt_exam_id}/cheat-warning', [StudentCbtController::class, 'cheatWarning'])->name('student.cbt.cheat-warning');
});
