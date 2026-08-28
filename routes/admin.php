<?php

use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ActivityLogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Akademik\Http\Controllers\AcademicCalendarController;
use Modules\Akademik\Http\Controllers\AcademicYearController;
use Modules\Akademik\Http\Controllers\AdminScheduleViewController;
use Modules\Akademik\Http\Controllers\ClassroomController;
use Modules\Akademik\Http\Controllers\CurriculumDashboardController;
use Modules\Akademik\Http\Controllers\ScheduleController;
use Modules\Akademik\Http\Controllers\StudentController;
use Modules\Akademik\Http\Controllers\SubjectController;
use Modules\Akademik\Http\Controllers\TeacherController;
use Modules\Akademik\Http\Controllers\TeachingLoadController;

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

/*
| Dashboard
*/
Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->name('dashboard');
Route::get('/dashboard/api/teachers', [\App\Http\Controllers\Admin\DashboardController::class, 'getTeacherDetails'])
    ->name('dashboard.api.teachers');
Route::get('/dashboard/api/students', [\App\Http\Controllers\Admin\DashboardController::class, 'getStudentDetails'])
    ->name('dashboard.api.students');

/*
| Impersonation
*/
Route::post('impersonate/{id}', [\App\Http\Controllers\Admin\ImpersonateController::class, 'take'])
    ->name('users.impersonate');


/*
| Tahun Ajaran
*/
Route::resource('academic-years', AcademicYearController::class)
    ->except(['create', 'show', 'edit']);

Route::put('academic-years/{academicYear}/activate', [AcademicYearController::class, 'setActive'])
    ->name('academic-years.activate');
Route::put('academic-years/{academicYear}/school-days', [AcademicYearController::class, 'updateSchoolDays'])->name('academic-years.update-school-days');
Route::get('/academic-years/{academicYear}/school-days', [AcademicYearController::class, 'editSchoolDays'])->name('academic-years.school-days');

/*
| Guru
*/
Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
Route::put('teachers/{id}', [TeacherController::class, 'update'])->name('teachers.update');
Route::delete('teachers/{id}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

Route::put('teachers/{id}/restore', [TeacherController::class, 'restore'])->name('teachers.restore');
Route::delete('teachers/{id}/force-delete', [TeacherController::class, 'forceDelete'])->name('teachers.force-delete');

Route::get('teachers/export/template', [TeacherController::class, 'downloadTemplate'])->name('teachers.export.template');
Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');

/*
| Kelas
*/
// ====== STATIC ROUTES (HARUS DI ATAS) ======
Route::post('classrooms/copy-previous-year', [ClassroomController::class, 'copyFromPreviousYear'])
    ->name('classrooms.copy-previous-year');

Route::get('classrooms/promotion', [ClassroomController::class, 'promotionIndex'])
    ->name('classrooms.promotion.index');

Route::get('classrooms/promotion/students', [ClassroomController::class, 'getPromotionStudents'])
    ->name('classrooms.promotion.students');

Route::post('classrooms/promotion', [ClassroomController::class, 'promoteStudents'])
    ->name('classrooms.promotion.store');

Route::get('classrooms/by-year/{yearId}', [ClassroomController::class, 'getClassroomsByYear'])
    ->name('classrooms.by-year');

Route::get('classrooms/template', [ClassroomController::class, 'downloadTemplate'])
    ->name('classrooms.template');

Route::post('classrooms/import', [ClassroomController::class, 'import'])
    ->name('classrooms.import');

Route::get('classrooms/members/template', [ClassroomController::class, 'downloadMemberTemplate'])
    ->name('classrooms.members.template');

Route::post('classrooms/members/import', [ClassroomController::class, 'importMembers'])
    ->name('classrooms.members.import');

Route::get('students/available', [ClassroomController::class, 'getAvailableStudents'])
    ->name('students.available');

// ====== DYNAMIC ROUTES (DI BAWAH) ======
Route::get('classrooms', [ClassroomController::class, 'index'])
    ->name('classrooms.index');
Route::get('classrooms/{id}', [ClassroomController::class, 'show'])
    ->whereNumber('id')
    ->name('classrooms.show');

Route::put('classrooms/{id}', [ClassroomController::class, 'update'])
    ->whereNumber('id')
    ->name('classrooms.update');

Route::delete('classrooms/{id}', [ClassroomController::class, 'destroy'])
    ->whereNumber('id')
    ->name('classrooms.destroy');

Route::put('classrooms/{id}/restore', [ClassroomController::class, 'restore'])
    ->whereNumber('id')
    ->name('classrooms.restore');

Route::delete('classrooms/{id}/force-delete', [ClassroomController::class, 'forceDelete'])
    ->whereNumber('id')
    ->name('classrooms.force-delete');

Route::post('classrooms/{id}/members', [ClassroomController::class, 'addMembers'])
    ->whereNumber('id')
    ->name('classrooms.add-members');

Route::delete('classrooms/{id}/members/{studentId}', [ClassroomController::class, 'removeMember'])
    ->whereNumber('id')
    ->whereNumber('studentId')
    ->name('classrooms.remove-member');

/*
| Siswa
*/
Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
Route::get('students', [StudentController::class, 'index'])->name('students.index');
Route::post('students', [StudentController::class, 'store'])->name('students.store');
Route::put('students/{id}', [StudentController::class, 'update'])->name('students.update');
Route::delete('students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

Route::put('students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
Route::delete('students/{id}/force-delete', [StudentController::class, 'forceDelete'])->name('students.force-delete');

Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
Route::get('students/template-update', [StudentController::class, 'downloadTemplateUpdate'])->name('students.template-update');
Route::post('students/import-update', [StudentController::class, 'importDataKosong'])->name('students.import-update');
Route::post('students/generate-nis', [StudentController::class, 'generateNis'])->name('students.generate-nis');
Route::post('students/bulk-update-nis', [StudentController::class, 'bulkUpdateNis'])->name('students.bulk-update-nis');
Route::get('students/export-bulk-nis', [StudentController::class, 'exportBulkNis'])->name('students.export-bulk-nis');
Route::post('students/import-bulk-nis', [StudentController::class, 'importBulkNis'])->name('students.import-bulk-nis');

/*
| Mata Pelajaran
*/
Route::resource('subjects', SubjectController::class)
    ->except(['create', 'show', 'edit']);

Route::put('subjects/{id}/restore', [SubjectController::class, 'restore'])->name('subjects.restore');
Route::delete('subjects/{id}/force-delete', [SubjectController::class, 'forceDelete'])->name('subjects.force-delete');
Route::put('subjects/{subject}/mapping', [SubjectController::class, 'updateMapping'])->name('subjects.mapping.update');
Route::get('subjects/template', [SubjectController::class, 'downloadTemplate'])->name('subjects.template');
Route::post('subjects/import', [SubjectController::class, 'import'])->name('subjects.import');

/*
| Jadwal & Monitoring
*/
Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
Route::get('schedules/{classroom}/manage', [ScheduleController::class, 'manage'])->name('schedules.manage');
Route::put('schedules/{classroom}', [ScheduleController::class, 'update'])->name('schedules.update');

Route::get('schedules/{id}/details', [ScheduleController::class, 'getDetails'])->name('schedules.details.get');
Route::post('schedules/{id}/details', [ScheduleController::class, 'updateDetails'])->name('schedules.details.update');

Route::get('monitoring/schedule-class', [AdminScheduleViewController::class, 'byClass'])->name('monitoring.schedule.class');
Route::get('monitoring/schedule-teacher', [AdminScheduleViewController::class, 'byTeacher'])->name('monitoring.schedule.teacher');
Route::post('monitoring/schedule-teacher/reset', [AdminScheduleViewController::class, 'resetSchedules'])->name('monitoring.schedule.reset');

/*
| Kalender Akademik (ADMIN – INPUT)
*/
Route::prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [AcademicCalendarController::class, 'index'])->name('index');
    Route::post('/', [AcademicCalendarController::class, 'store'])->name('store');
    Route::delete('{id}', [AcademicCalendarController::class, 'destroy'])->name('destroy');
});
/*
| Kuriulum Dashboard
*/
Route::get('curriculum/dashboard', [CurriculumDashboardController::class, 'index'])->name('curriculum.dashboard');
Route::get('/teachers/{teacher}/workload', [TeachingLoadController::class, 'show'])->name('teachers.workload');
/*
|Admin Menu Management
*/
Route::resource('menus', MenuController::class)->only(['index', 'store', 'update', 'destroy']);
Route::patch('menus/{menu}/toggle', [MenuController::class, 'toggle'])->name('menus.toggle');

/*
| Manajemen User Admin (RESET PASSWORD & AKTIVASI/DEAKTIVASI)
*/
Route::resource('users', UserController::class);

// Custom logic routes
Route::patch('users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
Route::post('users/batch-disable', [UserController::class, 'batchDisable'])->name('users.batch-disable');
Route::post('users/batch-enable', [UserController::class, 'batchEnable'])->name('users.batch-enable');

/*
| Manajemen User Permision
*/
// 1. Laman Utama: Menampilkan daftar fitur dan personil
Route::get('access-control', [AccessControlController::class, 'index'])
    ->name('access-control.index');

// 2. Tambah Fitur: Membuat nama permission baru (misal: manage-canteen)
Route::post('access-control/permission', [AccessControlController::class, 'storePermission'])
    ->name('access-control.store-perm');

// 3. Tambah Personil: Menugaskan Guru/Staf ke suatu fitur
Route::post('access-control/assign', [AccessControlController::class, 'assignUser'])
    ->name('access-control.assign');

// 4. Cabut Akses: Menghapus Guru/Staf dari suatu fitur
Route::post('access-control/revoke', [AccessControlController::class, 'revokeUser'])
    ->name('access-control.revoke');

// 5. Sinkronisasi Modul: Membuat permission otomatis untuk setiap modul
Route::post('access-control/sync-modules', [AccessControlController::class, 'syncModules'])
    ->name('access-control.sync-modules');

/*
|--------------------------------------------------------------------------
| Backup Management
|--------------------------------------------------------------------------
| Kita buat route untuk manajemen backup manual, walaupun backup otomatis sudah diatur di config/backup.php
*/
Route::prefix('/backups')->name('backups.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::get('/check-s3', [BackupController::class, 'checkS3Connection'])->name('check-s3');
    Route::post('/run', [BackupController::class, 'create'])->name('run');
    Route::post('/sync-s3/{file_name}', [BackupController::class, 'syncToS3'])->name('sync-s3');
    Route::post('/restore-upload', [BackupController::class, 'restoreFromUpload'])->name('restore-upload');
    Route::post('/restore/{file_name}', [BackupController::class, 'restore'])->name('restore');
    Route::get('/download/{file_name}', [BackupController::class, 'download'])->name('download');
    Route::delete('/{file_name}', [BackupController::class, 'destroy'])->name('destroy');

});

/*
| Pengaturan Situs
*/
Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

/*
| Log Aktivitas & Audit Trail
*/
Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

/*
|--------------------------------------------------------------------------
| Sarana & Prasarana (Sistem Peminjaman Ruang & Aset)
|--------------------------------------------------------------------------
*/
Route::prefix('sarpras')->name('sarpras.')->group(function () {
    // Ruangan
    Route::get('rooms/template', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityRoomController::class, 'downloadTemplate'])->name('rooms.template');
    Route::post('rooms/import', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityRoomController::class, 'import'])->name('rooms.import');
    Route::resource('rooms', \App\Http\Controllers\Admin\Sarpras\AdminFacilityRoomController::class)->except(['create', 'show', 'edit']);
    
    // Aset & Label QR
    Route::get('assets/template', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityAssetController::class, 'downloadTemplate'])->name('assets.template');
    Route::post('assets/import', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityAssetController::class, 'import'])->name('assets.import');
    Route::get('assets/print-qr', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityAssetController::class, 'printQrCodes'])->name('assets.print-qr');
    Route::resource('assets', \App\Http\Controllers\Admin\Sarpras\AdminFacilityAssetController::class)->except(['create', 'show', 'edit']);
    
    // Reservasi & Approval Tahap 2
    Route::get('reservations/calendar', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityReservationController::class, 'calendar'])->name('reservations.calendar');
    Route::get('reservations/calendar-events', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityReservationController::class, 'calendarEvents'])->name('reservations.calendar-events');
    Route::get('reservations/export-pdf', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityReservationController::class, 'exportPdf'])->name('reservations.export-pdf');
    Route::post('reservations/{id}/approve', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityReservationController::class, 'approve'])->name('reservations.approve');
    Route::post('reservations/{id}/reject', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityReservationController::class, 'reject'])->name('reservations.reject');
    Route::resource('reservations', \App\Http\Controllers\Admin\Sarpras\AdminFacilityReservationController::class)->only(['index', 'show']);
    
    // Scanner Serah Terima & Pengembalian
    Route::get('scanner', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityHandoverController::class, 'scannerIndex'])->name('scanner.index');
    Route::post('scanner/verify', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityHandoverController::class, 'verifyQr'])->name('scanner.verify');
    Route::post('scanner/handover', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityHandoverController::class, 'submitHandover'])->name('scanner.handover');
    Route::post('scanner/return', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityHandoverController::class, 'submitReturn'])->name('scanner.return');
    
    // Berita Acara Kerusakan
    Route::get('damages', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityDamageController::class, 'index'])->name('damages.index');
    Route::put('damages/{id}', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityDamageController::class, 'updateStatus'])->name('damages.update');
    Route::get('damages/{id}/pdf', [\App\Http\Controllers\Admin\Sarpras\AdminFacilityDamageController::class, 'exportPdf'])->name('damages.pdf');
    
    // Blackouts (Jadwal Khusus Sekolah)
    Route::resource('blackouts', \App\Http\Controllers\Admin\Sarpras\AdminFacilityBlackoutController::class)->only(['index', 'store', 'destroy']);
});

