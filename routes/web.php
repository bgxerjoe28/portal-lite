<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
// Admin Controllers
use App\Http\Middleware\CheckIfActive;
// Akademik Controllers
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
use Inertia\Inertia;
use Modules\Akademik\Http\Controllers\AcademicCalendarController;
use Modules\Akademik\Http\Controllers\AcademicYearController;
use Modules\Akademik\Http\Controllers\AdminScheduleViewController;
use Modules\Akademik\Http\Controllers\AgendaReportController;
use Modules\Akademik\Http\Controllers\ClassroomController;
use Modules\Akademik\Http\Controllers\CurriculumDashboardController;
use Modules\Akademik\Http\Controllers\Guru\GuruAgendaController;
use Modules\Akademik\Http\Controllers\Guru\GuruCPController;
use Modules\Akademik\Http\Controllers\Guru\GuruDashboardController;
use Modules\Akademik\Http\Controllers\Guru\GuruTPController;
use Modules\Akademik\Http\Controllers\Guru\LateStudentController;
use Modules\Akademik\Http\Controllers\Guru\StudentPermitController;
use Modules\Akademik\Http\Controllers\Guru\TeachingScheduleController;
use Modules\Akademik\Http\Controllers\LearningObjectiveTPController;
use Modules\Akademik\Http\Controllers\LearningOutcomeCPController;
use Modules\Akademik\Http\Controllers\ScheduleController;
use Modules\Akademik\Http\Controllers\StudentController;
use Modules\Akademik\Http\Controllers\SubjectController;
use Modules\Akademik\Http\Controllers\TeacherController;
use Modules\Akademik\Http\Controllers\TeachingLoadController;
use Modules\Penilaian\Http\Controllers\GradeController;
use Modules\Penilaian\Http\Controllers\GradingComponentController;
*/
/*
|--------------------------------------------------------------------------
| GUEST
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('guest')->group(function () {
    Route::get('lupa-password', [PasswordResetController::class, 'showVerifyForm'])->name('password.verify.form');
    Route::post('lupa-password', [PasswordResetController::class, 'verifyAndReset'])->name('password.verify.submit');
    Route::get('lupa-email', [PasswordResetController::class, 'showForgotEmailForm'])->name('email.forgot.form');
    Route::post('lupa-email', [PasswordResetController::class, 'lookupEmail'])->name('email.forgot.submit');
});

/*
limiter
*/
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    if (Auth::user()->hasRole('guru')) {
        return redirect('/teacher/dashboard');
    }

    if (Auth::user()->hasRole('admin')) {
        return redirect('/admin/dashboard');
    }
    if (Auth::user()->hasRole('siswa')) {
        return redirect('/student/dashboard');
    }
    if (Auth::user()->hasRole('pegawai') || Auth::user()->hasRole('staf') || Auth::user()->hasRole('staff')) {
        return redirect('/staf/dashboard');
    }
    if (Auth::user()->hasRole('kepala sekolah') || Auth::user()->hasRole('kepsek')) {
        return redirect('/ks/dashboard');
    }
    if (Auth::user()->hasRole('guru bk')) {
        return redirect('/bk/dashboard');
    }

    return abort(403);
});
Route::middleware(['auth', 'role:admin', 'verified', CheckIfActive::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        require __DIR__.'/admin.php';
    });

Route::middleware(['auth', CheckIfActive::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('impersonate-leave', [\App\Http\Controllers\Admin\ImpersonateController::class, 'leave'])
            ->name('users.impersonate.leave');

        Route::prefix('facility-reports')->name('facility.reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'index'])->name('index');
            Route::get('/recap', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'itemsIndex'])->name('recap');
            Route::get('/export-pdf', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'show'])->name('show');
            Route::post('/{id}/update', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'addUpdate'])->name('add-update');
            Route::post('/{id}/items', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'storeItems'])->name('items.store');
            Route::put('/items/{itemId}', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'updateItem'])->name('items.update-full');
            Route::patch('/items/{itemId}/status', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'updateItemStatus'])->name('items.update');
            Route::delete('/items/{itemId}', [\App\Http\Controllers\Admin\AdminFacilityReportController::class, 'destroyItem'])->name('items.destroy');
        });
    });

Route::middleware(['auth', CheckIfActive::class])
    ->prefix('facility-reports')
    ->name('facility.reports.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\FacilityReportController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\FacilityReportController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FacilityReportController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\FacilityReportController::class, 'show'])->name('show');
    });
Route::middleware(['auth', 'role:guru', CheckIfActive::class])
    ->prefix('teacher')
    ->name('guru.')
    ->group(function () {
        require __DIR__.'/teacher.php';
    });
Route::middleware(['auth', 'role:siswa', CheckIfActive::class])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        require __DIR__.'/student.php';
    });
Route::middleware(['auth', 'role:pegawai|staf|staff', CheckIfActive::class])
    ->prefix('staf')
    ->name('staf.')
    ->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Staff\StaffDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:kepala sekolah|kepsek', CheckIfActive::class])
    ->prefix('ks')
    ->name('ks.')
    ->group(function () {
        Route::get('/dashboard', [\Modules\Akademik\Http\Controllers\Kepsek\KepsekDashboardController::class, 'index'])->name('dashboard');
        Route::get('/agenda/{id}', [\Modules\Akademik\Http\Controllers\Kepsek\KepsekDashboardController::class, 'showAgenda'])->name('agenda.show');
        Route::get('/teacher/{id}/history', [\Modules\Akademik\Http\Controllers\Kepsek\KepsekDashboardController::class, 'teacherHistory'])->name('teacher.history');
        Route::get('/late-students', [\Modules\Akademik\Http\Controllers\Kepsek\KepsekDashboardController::class, 'lateStudents'])->name('lates');
        Route::get('/alfa-students', [\Modules\Akademik\Http\Controllers\Kepsek\KepsekDashboardController::class, 'alfaStudents'])->name('alfas');
    });

Route::middleware(['auth', 'role:guru bk', CheckIfActive::class])
    ->prefix('bk')
    ->name('bk.')
    ->group(function () {
        Route::get('/dashboard', [\Modules\Akademik\Http\Controllers\GuruBK\GuruBKDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/details', [\Modules\Akademik\Http\Controllers\GuruBK\GuruBKDashboardController::class, 'details'])->name('dashboard.details');
    });

Route::get('/dashboard', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    if (Auth::user()->hasRole('guru')) {
        return redirect('/teacher/dashboard');
    }
    if (Auth::user()->hasRole('admin')) {
        return redirect('/admin/dashboard');
    }
    if (Auth::user()->hasRole('siswa')) {
        return redirect('/student/dashboard');
    }
    if (Auth::user()->hasRole('pegawai') || Auth::user()->hasRole('staf') || Auth::user()->hasRole('staff')) {
        return redirect('/staf/dashboard');
    }
    if (Auth::user()->hasRole('kepala sekolah') || Auth::user()->hasRole('kepsek')) {
        return redirect('/ks/dashboard');
    }
    if (Auth::user()->hasRole('guru bk')) {
        return redirect('/bk/dashboard');
    }
    return abort(403);
})->middleware(['auth'])->name('dashboard.redirect');
/*
|--------------------------------------------------------------------------
| AUTH UMUM
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/change-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

// Rute pengecekan status server
Route::get('/octane-status', function () {
    return response()->json([
        'status' => 'OK',
        'is_octane' => isset($_SERVER['LARAVEL_OCTANE']) && $_SERVER['LARAVEL_OCTANE'] == 1,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]);
});

// Webhook Sinkronisasi Google Sheets untuk Izin Siswa
Route::post('/api/permits/sync-sheet', [\Modules\Akademik\Http\Controllers\Api\GoogleSheetPermitWebhookController::class, 'syncSheet'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
