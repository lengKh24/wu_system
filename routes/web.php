<?php

use App\Http\Controllers\Admin\ExamScheduleController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentStatusController;
use App\Http\Controllers\StateExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

// Public — on-site exam staff, no login required.
Route::prefix('state-exam/attendance')->name('state-exam.attendance.')->group(function () {
    Route::get('/', [StateExamController::class, 'attendance'])->name('index');
    Route::get('/{round}', [StateExamController::class, 'attendanceSearch'])->name('search');
});

Route::get('/state-exam/invigilators', [StateExamController::class, 'invigilators'])->name('state-exam.invigilators.index');

Route::middleware(['auth'])->group(function () {
    // This is the missing piece that connects to your Controller
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('lecturer', LecturerController::class)->only('index')->middleware('can:lecturer.view');
    Route::resource('shift', ShiftController::class)->only('index')->middleware('can:shift.view');
    Route::resource('faculty', FacultyController::class)->only('index')->middleware('can:faculty.view');
    Route::resource('major', MajorController::class)->only('index')->middleware('can:major.view');
    Route::resource('batch', BatchController::class)->only('index')->middleware('can:batch.view');
    Route::resource('group', GroupController::class)->only('index')->middleware('can:group.view');
    Route::resource('campus', CampusController::class)->only('index')->middleware('can:campus.view');

    Route::middleware('can:state-exam.view')->group(function () {
        Route::resource('state-exam', StateExamController::class)->only('index');
        Route::get('state-exam/report', [StateExamController::class, 'report'])->name('state-exam.report');
        Route::get('/exam-schedule', ExamScheduleController::class)->name('exam.schedule');
    });

    Route::resource('alert', AlertController::class)->only('index')->middleware('can:alert.view');

    Route::resource('student', StudentController::class)->only('index')->middleware('can:student.view');
    Route::resource('certificate', StudentStatusController::class)->only('index')->middleware('can:certificate.view');
    Route::resource('app-status', StatusController::class)->only('index')->middleware('can:app-status.view');

    Route::resource('role', RoleController::class)->only('index')->middleware('can:role.view');
    Route::resource('activity', ActivityLogController::class)->only('index')->middleware('can:activity.view');

    Route::resource('profile', ProfileController::class)->only(['edit', 'update', 'destroy']);
});

require __DIR__ . '/auth.php';
