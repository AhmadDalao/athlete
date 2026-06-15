<?php

use App\Http\Controllers\Admin\ControlCenterController;
use App\Http\Controllers\Admin\UserIndexController;
use App\Http\Controllers\Admin\UserUpdateController;
use App\Http\Controllers\AthleteCheckInStoreController;
use App\Http\Controllers\AthleteCheckInUpdateController;
use App\Http\Controllers\ContactPageController;
use App\Http\Controllers\ContactSubmissionStoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MembershipIndexController;
use App\Http\Controllers\MembershipUpdateController;
use App\Http\Controllers\PaymentEventStoreController;
use App\Http\Controllers\ProgressIndexController;
use App\Http\Controllers\RosterAssignmentStoreController;
use App\Http\Controllers\RosterAssignmentUpdateController;
use App\Http\Controllers\RosterIndexController;
use App\Http\Controllers\TrainingIndexController;
use App\Http\Controllers\TrainingProgramStoreController;
use App\Http\Controllers\TrainingSessionStoreController;
use App\Http\Controllers\WearableIndexController;
use App\Http\Controllers\WebsiteHomeController;
use App\Http\Controllers\WhoopCallbackController;
use App\Http\Controllers\WhoopConnectController;
use App\Http\Controllers\WorkoutLogStoreController;
use Illuminate\Support\Facades\Route;

Route::any('/', WebsiteHomeController::class)->name('home');
Route::get('contact', ContactPageController::class)->name('contact.show');
Route::post('contact', ContactSubmissionStoreController::class)
    ->middleware('throttle:6,1')
    ->name('contact.store');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('progress', ProgressIndexController::class)->name('progress.index');
    Route::post('progress/check-ins', AthleteCheckInStoreController::class)->name('progress.check-ins.store');
    Route::patch('progress/check-ins/{athleteCheckIn}', AthleteCheckInUpdateController::class)->name('progress.check-ins.update');
    Route::get('roster', RosterIndexController::class)->name('roster.index');
    Route::post('roster/assignments', RosterAssignmentStoreController::class)->name('roster.assignments.store');
    Route::patch('roster/assignments/{assignment}', RosterAssignmentUpdateController::class)->name('roster.assignments.update');
    Route::get('training', TrainingIndexController::class)->name('training.index');
    Route::post('training/programs', TrainingProgramStoreController::class)->name('training.programs.store');
    Route::post('training/programs/{trainingProgram}/sessions', TrainingSessionStoreController::class)->name('training.programs.sessions.store');
    Route::post('training/sessions/{trainingSession}/log', WorkoutLogStoreController::class)->name('training.sessions.log.store');
    Route::get('memberships', MembershipIndexController::class)->name('memberships.index');
    Route::patch('memberships/{membership}', MembershipUpdateController::class)->name('memberships.update');
    Route::post('memberships/{membership}/events', PaymentEventStoreController::class)->name('memberships.events.store');
    Route::get('wearables', WearableIndexController::class)->name('wearables.index');
    Route::get('wearables/whoop/connect', WhoopConnectController::class)->name('wearables.whoop.connect');
    Route::get('wearables/whoop/callback', WhoopCallbackController::class)->name('wearables.whoop.callback');
    Route::get('admin/control-center', ControlCenterController::class)->name('admin.control-center');
    Route::get('admin/users', UserIndexController::class)->name('admin.users.index');
    Route::patch('admin/users/{user}', UserUpdateController::class)->name('admin.users.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
