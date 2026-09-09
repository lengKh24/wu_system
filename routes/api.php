<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CampusController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamStateController;
use App\Http\Controllers\Api\ExamTypeController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\PaymentBatchController;
use App\Http\Controllers\Api\PaymentEntryController;
use App\Http\Controllers\Api\RetakeBatchController;
use App\Http\Controllers\Api\RetakeExamPublicController;
use App\Http\Controllers\Api\RetakeRegistrationController;
use App\Http\Controllers\Api\RetakeTermController;
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

// Public self-service retake-exam registration — same reasoning as
// exam-states above. There's no login/session here: every action
// re-verifies ownership with student code + date of birth rather than
// trusting a token, so this can live fully outside the 'auth' group. The
// page itself is served from routes/web.php's retake-exam.index.
Route::prefix('v1/retake-exam')->name('retake-exam-public.')->group(function () {
    Route::post('/lookup', [RetakeExamPublicController::class, 'lookup'])->name('lookup');
    Route::post('/select', [RetakeExamPublicController::class, 'select'])->name('select');
    Route::post('/confirm', [RetakeExamPublicController::class, 'confirm'])->name('confirm');
});

Route::prefix('v1')->middleware('auth')->group(function () {
    // No permission gate — matches the /dashboard page itself, which is
    // visible to any authenticated user regardless of role.
    Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

    // Custom API routes for specific controllers
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/preview-number', [CertificateController::class, 'preview'])->name('preview');
        Route::get('/report', [CertificateController::class, 'report'])->name('report');
    });

    // Retake Exam module — rebuilt on the normalized schema (retake_terms,
    // exam_types, retake_batches, retake_registrations, payment_batches,
    // payment_entries, scores, deletion_log). See the design doc in the WU
    // System project. retake-terms/exam-types/retake-batches/
    // payment-batches/payment-entries get standard CRUD via api_routes()
    // below; retake-registrations is hand-routed since most of what REG/
    // SA/Score/ACC do to a registration isn't a generic "update".
    Route::middleware('permission:retake-registration.view')->prefix('retake-registrations')->name('retake-registrations.')->group(function () {
        Route::get('/', [RetakeRegistrationController::class, 'index'])->name('index');
        Route::get('/{retake_registration}', [RetakeRegistrationController::class, 'show'])->name('show');
    });

    // REG's "prepare schedule" export — flat sibling route (like
    // customer-service below), not nested under /retake-registrations/...,
    // so it never collides with the {retake_registration} wildcard above.
    Route::middleware('permission:retake-registration.view')
        ->get('/retake-registrations-export', [RetakeRegistrationController::class, 'exportList'])
        ->name('retake-registrations.export');

    // REG's report page — same flat-sibling reasoning as export above.
    Route::middleware('permission:retake-registration.view')
        ->get('/retake-registrations-report', [RetakeRegistrationController::class, 'report'])
        ->name('retake-registrations.report');

    // Customer Service: read-only, registered students only — its own
    // permission, separate from REG's full retake-registration.view.
    Route::middleware('permission:retake-cs.view')
        ->get('/retake-registrations-customer-service', [RetakeRegistrationController::class, 'customerService'])
        ->name('retake-registrations.customer-service');

    Route::middleware('permission:retake-registration.create')
        ->post('/retake-registrations', [RetakeRegistrationController::class, 'store'])
        ->name('retake-registrations.store');

    // Split into three permissions, not one shared "edit" — the design
    // doc's RBAC notes are explicit that SA shouldn't be able to touch
    // scores and Score shouldn't be able to touch payments, so each
    // role's action lives behind its own permission rather than a coarse
    // retake-registration.edit that everyone acting on a registration
    // would need.
    Route::middleware('permission:retake-payment.edit')->prefix('retake-registrations')->name('retake-registrations.')->group(function () {
        Route::patch('/bulk-mark-paid', [RetakeRegistrationController::class, 'bulkMarkPaid'])->name('bulk-mark-paid');
        Route::patch('/{retake_registration}/mark-paid', [RetakeRegistrationController::class, 'markPaid'])->name('mark-paid');
        Route::patch('/{retake_registration}/invite-telegram', [RetakeRegistrationController::class, 'inviteTelegram'])->name('invite-telegram');
    });

    Route::middleware('permission:retake-score.edit')
        ->patch('/retake-registrations/{retake_registration}/score', [RetakeRegistrationController::class, 'setScore'])
        ->name('retake-registrations.score');

    Route::middleware('permission:retake-registration.edit')->prefix('retake-registrations')->name('retake-registrations.')->group(function () {
        Route::patch('/{retake_registration}/outcome', [RetakeRegistrationController::class, 'setOutcome'])->name('outcome');
        // Manual override, post-lock (decision #15) — REG only for now;
        // revisit if SA turns out to need this too at the front desk.
        Route::patch('/{retake_registration}/selection', [RetakeRegistrationController::class, 'updateSelection'])->name('selection');
        Route::patch('/{retake_registration}/restore', [RetakeRegistrationController::class, 'restore'])->withTrashed()->name('restore');
    });

    Route::middleware('permission:retake-registration.delete')->prefix('retake-registrations')->name('retake-registrations.')->group(function () {
        Route::delete('/{retake_registration}', [RetakeRegistrationController::class, 'destroy'])->name('destroy');
        Route::delete('/{retake_registration}/clear', [RetakeRegistrationController::class, 'force_destroy'])->name('remove');
    });

    // REG imports the 1st Supplementary URM export — creates its own new
    // batch, so this is a "create", not an "edit" on an existing one.
    Route::middleware('permission:retake-batch.create')
        ->post('/retake-batches/import', [RetakeBatchController::class, 'importFile'])
        ->name('retake-batches.import');

    // REG's batch actions: close a stage, then generate the next one from
    // it, or attach a Telegram group.
    Route::middleware('permission:retake-batch.edit')->prefix('retake-batches')->name('retake-batches.')->group(function () {
        Route::patch('/{retake_batch}/close', [RetakeBatchController::class, 'close'])->name('close');
        Route::post('/{retake_batch}/carry-forward', [RetakeBatchController::class, 'carryForward'])->name('carry-forward');
        Route::patch('/{retake_batch}/telegram', [RetakeBatchController::class, 'telegram'])->name('telegram');
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
    Route::middleware('permission:role.edit')->put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::middleware('permission:activity.view')->prefix('activity-log')->name('activity-log.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/modules', [ActivityLogController::class, 'modules'])->name('modules');
        Route::get('/users', [ActivityLogController::class, 'users'])->name('users');
    });

    // Flat sibling routes for students — registered before api_routes()'s
    // /students/{student} wildcard below so "export"/"import" are never
    // swallowed as an id (same reasoning as retake-registrations-export).
    Route::middleware('permission:student.view')
        ->get('/students-export', [StudentController::class, 'exportList'])
        ->name('students.export');
    Route::middleware('permission:student.create')
        ->post('/students-import', [StudentController::class, 'importFile'])
        ->name('students.import');
    Route::middleware('permission:student.delete')
        ->delete('/students-bulk-destroy', [StudentController::class, 'bulkDestroy'])
        ->name('students.bulk-destroy');

    // Register API resource routes for various controllers
    api_routes([
        'faculties'       => FacultyController::class,
        'majors'          => MajorController::class,
        'shifts'          => ShiftController::class,
        'campuses'        => CampusController::class,
        'lecturers'       => LecturerController::class,
        'subjects'        => SubjectController::class,
        'batches'         => BatchController::class,
        'groups'          => GroupController::class,
        'students'        => StudentController::class,
        'statuses'        => StatusController::class,
        'certificates'    => CertificateController::class,
        'alerts'          => AlertController::class,
        'retake-terms'    => RetakeTermController::class,
        'exam-types'      => ExamTypeController::class,
        'retake-batches'  => RetakeBatchController::class,
        'payment-batches' => PaymentBatchController::class,
        'payment-entries' => PaymentEntryController::class,
        'users'           => UserController::class,
    ], [
        'faculties'       => 'faculty',
        'majors'          => 'major',
        'shifts'          => 'shift',
        'campuses'        => 'campus',
        'lecturers'       => 'lecturer',
        'subjects'        => 'subject',
        'batches'         => 'batch',
        'groups'          => 'group',
        'students'        => 'student',
        'statuses'        => 'app-status',
        'certificates'    => 'certificate',
        'alerts'          => 'alert',
        'retake-terms'    => 'retake-term',
        'exam-types'      => 'exam-type',
        'retake-batches'  => 'retake-batch',
        'payment-batches' => 'payment-batch',
        'payment-entries' => 'payment-entry',
        // 'exam-states' registered separately above, fully public.
        'users'           => 'role',
    ]);

    // Address API routes
    Route::get('/provinces', [AddressController::class, 'provinces'])->name('provinces.all');
    Route::get('/nationalities', [AddressController::class, 'nationalities'])->name('nationalities.all');

    // Using Model Binding
    Route::get('/districts/{province}', [AddressController::class, 'districts'])->name('districts.by-province');
    Route::get('/communes/{district}', [AddressController::class, 'communes'])->name('communes.by-district');
    Route::get('/villages/{commune}', [AddressController::class, 'villages'])->name('villages.by-commune');
});
