<?php

use App\Http\Controllers\Api\AdminControlCenterController;
use App\Http\Controllers\Api\AthleteCheckInStoreController;
use App\Http\Controllers\Api\AthleteCheckInUpdateController;
use App\Http\Controllers\Api\AuthTokenDestroyController;
use App\Http\Controllers\Api\AuthTokenStoreController;
use App\Http\Controllers\Api\DashboardIndexController;
use App\Http\Controllers\Api\DeviceMetricIngestController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MembershipIndexController;
use App\Http\Controllers\Api\ProgressIndexController;
use App\Http\Controllers\Api\RosterIndexController;
use App\Http\Controllers\Api\TrainingSessionCompleteController;
use App\Http\Controllers\Api\TrainingSessionExecutionController;
use App\Http\Controllers\Api\TrainingSessionSetStoreController;
use App\Http\Controllers\Api\TrainingIndexController;
use App\Http\Controllers\Api\WearableIndexController;
use App\Http\Controllers\Api\WorkoutLogStoreController;
use Illuminate\Support\Facades\Route;

Route::post('device-connections/{deviceConnection}/ingest', DeviceMetricIngestController::class)
    ->middleware('throttle:30,1')
    ->name('api.device-connections.ingest');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/tokens', AuthTokenStoreController::class)
        ->middleware('throttle:10,1')
        ->name('auth.tokens.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/tokens/current', AuthTokenDestroyController::class)->name('auth.tokens.current.destroy');

        Route::get('me', MeController::class)
            ->middleware('abilities:profile:read')
            ->name('me');

        Route::get('dashboard', DashboardIndexController::class)
            ->middleware('abilities:dashboard:read')
            ->name('dashboard');

        Route::get('roster', RosterIndexController::class)
            ->middleware('abilities:roster:read')
            ->name('roster');

        Route::get('training', TrainingIndexController::class)
            ->middleware('abilities:training:read')
            ->name('training');

        Route::post('training/sessions/{trainingSession}/workout-log', WorkoutLogStoreController::class)
            ->middleware('abilities:training:write')
            ->name('training.sessions.workout-log.store');

        Route::get('training/sessions/{trainingSession}/execution', TrainingSessionExecutionController::class)
            ->middleware('abilities:training:read')
            ->name('training.sessions.execution');

        Route::post('training/sessions/{trainingSession}/sets', TrainingSessionSetStoreController::class)
            ->middleware('abilities:training:write')
            ->name('training.sessions.sets.store');

        Route::post('training/sessions/{trainingSession}/complete', TrainingSessionCompleteController::class)
            ->middleware('abilities:training:write')
            ->name('training.sessions.complete');

        Route::get('progress', ProgressIndexController::class)
            ->middleware('abilities:progress:read')
            ->name('progress');

        Route::post('progress/check-ins', AthleteCheckInStoreController::class)
            ->middleware('abilities:progress:write')
            ->name('progress.check-ins.store');

        Route::patch('progress/check-ins/{athleteCheckIn}', AthleteCheckInUpdateController::class)
            ->middleware('abilities:progress:write')
            ->name('progress.check-ins.update');

        Route::get('memberships', MembershipIndexController::class)
            ->middleware('abilities:membership:read')
            ->name('memberships');

        Route::get('wearables', WearableIndexController::class)
            ->middleware('abilities:wearable:read')
            ->name('wearables');

        Route::get('admin/control-center', AdminControlCenterController::class)
            ->middleware('abilities:admin:read')
            ->name('admin.control-center');
    });
});
