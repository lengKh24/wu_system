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
use App\Http\Controllers\RetakeCustomerServiceController;
use App\Http\Controllers\RetakeExamController;
use App\Http\Controllers\RetakeExamPublicController;
use App\Http\Controllers\RetakePaymentController;
use App\Http\Controllers\RetakePaymentEntryController;
use App\Http\Controllers\RetakeScoreController;
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

// Public — student self-service retake-exam registration, no login
// required. JSON actions live in routes/api.php's public group.
Route::get('/retake-exam', [RetakeExamPublicController::class, 'index'])->name('retake-exam.index');

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

    // REG's Main List for the retake exam module — batches + registrations
    // on one page. Named 'retake-registration.index' (not 'retake-exam.*')
    // to avoid clashing with the public self-service route above. Gated on
    // .edit (not .view) since SA/ACC/Score/CS all share .view too — this
    // page's batch/import/outcome actions are REG-only.
    Route::get('/retake-exam/registrations', [RetakeExamController::class, 'index'])
        ->name('retake-registration.index')
        ->middleware('can:retake-registration.edit');

    // Score's own page — read the registrations list, enter/edit a score.
    // No batch, import, outcome, selection, or delete access.
    Route::get('/retake-exam/scores', [RetakeScoreController::class, 'index'])
        ->name('retake-score.index')
        ->middleware('can:retake-score.edit');

    // SA's own page — confirmed registrations, mark paid, invite Telegram.
    Route::get('/retake-exam/payments', [RetakePaymentController::class, 'index'])
        ->name('retake-payment.index')
        ->middleware('can:retake-payment.edit');

    // ACC's own page — reconciliation entries against SA's payment batches.
    Route::get('/retake-exam/payment-entries', [RetakePaymentEntryController::class, 'index'])
        ->name('payment-entry.index')
        ->middleware('can:payment-entry.view');

    // Customer Service's own page — read-only, confirmed registrations only.
    Route::get('/retake-exam/customer-service', [RetakeCustomerServiceController::class, 'index'])
        ->name('retake-cs.index')
        ->middleware('can:retake-cs.view');

    Route::resource('student', StudentController::class)->only('index')->middleware('can:student.view');
    Route::resource('certificate', StudentStatusController::class)->only('index')->middleware('can:certificate.view');
    Route::resource('app-status', StatusController::class)->only('index')->middleware('can:app-status.view');

    Route::resource('role', RoleController::class)->only('index')->middleware('can:role.view');
    Route::resource('activity', ActivityLogController::class)->only('index')->middleware('can:activity.view');

    Route::resource('profile', ProfileController::class)->only(['edit', 'update', 'destroy']);
});

require __DIR__ . '/auth.php';
