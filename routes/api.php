<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CampusController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\ExamStateController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\RetakeExamController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Route Api for application
 */
// These two are admin-only actions and must be registered — and matched —
// before the public exam-states resource below, or its {exam_state}
// wildcard show route would swallow "/report" and "/bulk" as an id first.
Route::prefix('v1')->middleware('auth')->group(function () {
    Route::prefix('exam-states')->name('exam-states.')->group(function () {
        Route::get('/report', [ExamStateController::class, 'report'])->name('report');
        Route::delete('/bulk', [ExamStateController::class, 'bulkDestroy'])->name('bulk-destroy');
    });
});

// Exam-states stays fully public and unauthenticated — the on-site
// attendance/invigilator pages (routes/web.php's public state-exam.*
// group) have no login and PUT here directly to mark absences.
Route::prefix('v1')->group(function () {
    api_routes(['exam-states' => ExamStateController::class]);
});

Route::prefix('v1')->middleware('auth')->group(function () {
    // Custom API routes for specific controllers
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/preview-number', [CertificateController::class, 'preview'])->name('preview');
        Route::get('/report', [CertificateController::class, 'report'])->name('report');
    });

    Route::prefix('retake-exams')->name('retake-exams.')->group(function () {
        Route::get('/export', [RetakeExamController::class, 'exportExcel'])->name('export');
        Route::post('/import', [RetakeExamController::class, 'importExcel'])->name('import');
    });

    Route::middleware('permission:alert.view')->prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/dashboard', [AlertController::class, 'dashboard'])->name('dashboard');
        Route::get('/{alert}/logs', [AlertController::class, 'logs'])->name('logs');
    });
    Route::middleware('permission:alert.edit')->prefix('alerts')->name('alerts.')->group(function () {
        Route::post('/{alert}/complete', [AlertController::class, 'complete'])->name('complete');
        Route::post('/{alert}/snooze', [AlertController::class, 'snooze'])->name('snooze');
    });
    Route::middleware('permission:alert.delete')->delete('/alerts/bulk', [AlertController::class, 'bulkDestroy'])->name('alerts.bulk-destroy');

    Route::middleware('permission:role.view')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/permission-catalog', [RoleController::class, 'permissionCatalog'])->name('permission-catalog');
    });
    Route::middleware('permission:role.create')->post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::middleware('permission:role.edit')->put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::middleware('permission:role.delete')->delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::middleware('permission:role.edit')->put('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');

    Route::middleware('permission:activity.view')->prefix('activity-log')->name('activity-log.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/modules', [ActivityLogController::class, 'modules'])->name('modules');
        Route::get('/users', [ActivityLogController::class, 'users'])->name('users');
    });

    // Register API resource routes for various controllers
    api_routes([
        'faculties'    => FacultyController::class,
        'majors'       => MajorController::class,
        'shifts'       => ShiftController::class,
        'campuses'     => CampusController::class,
        'lecturers'    => LecturerController::class,
        'subjects'     => SubjectController::class,
        'batches'      => BatchController::class,
        'groups'       => GroupController::class,
        'students'     => StudentController::class,
        'statuses'     => StatusController::class,
        'certificates' => CertificateController::class,
        'alerts'       => AlertController::class,
        'retake-exams' => RetakeExamController::class,
        'users'        => UserController::class,
    ], [
        'faculties'    => 'faculty',
        'majors'       => 'major',
        'shifts'       => 'shift',
        'campuses'     => 'campus',
        'lecturers'    => 'lecturer',
        'subjects'     => 'subject',
        'batches'      => 'batch',
        'groups'       => 'group',
        'students'     => 'student',
        'statuses'     => 'app-status',
        'certificates' => 'certificate',
        'alerts'       => 'alert',
        // 'exam-states' registered separately above, fully public.
        'users'        => 'role',
    ]);

    // Address API routes
    Route::get('/provinces', [AddressController::class, 'provinces'])->name('provinces.all');
    Route::get('/nationalities', [AddressController::class, 'nationalities'])->name('nationalities.all');

    // Using Model Binding
    Route::get('/districts/{province}', [AddressController::class, 'districts'])->name('districts.by-province');
    Route::get('/communes/{district}', [AddressController::class, 'communes'])->name('communes.by-district');
    Route::get('/villages/{commune}', [AddressController::class, 'villages'])->name('villages.by-commune');
});
