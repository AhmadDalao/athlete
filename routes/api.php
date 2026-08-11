<?php

use App\Http\Controllers\Api\V1\AthleteAppController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CoachAppController;
use App\Http\Controllers\Api\V1\MessagingController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\WorkoutExecutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/status', fn () => response()->json([
        'data' => [
            'service' => 'Throughline API',
            'version' => 'v1',
            'status' => 'ok',
        ],
        'meta' => (object) [],
        'links' => (object) [],
    ]));

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    });

    Route::middleware(['auth:sanctum', 'organization'])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/tokens', [AuthController::class, 'revokeAll']);
        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations/{organization}/select', [OrganizationController::class, 'select']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/theme', [ProfileController::class, 'theme']);

        Route::middleware('can:messages.read')->group(function (): void {
            Route::get('/messages', [MessagingController::class, 'index']);
            Route::get('/messages/{conversation}', [MessagingController::class, 'show']);
            Route::post('/messages/{conversation}', [MessagingController::class, 'store'])->middleware('can:messages.send');
        });

        Route::middleware('can:athlete.access')->prefix('app')->group(function (): void {
            Route::get('/home', [AthleteAppController::class, 'home']);
            Route::get('/calendar', [AthleteAppController::class, 'calendar']);
            Route::get('/programs', [AthleteAppController::class, 'programs']);
            Route::get('/programs/{assignment}', [AthleteAppController::class, 'program']);
            Route::get('/workouts/{workout}', [AthleteAppController::class, 'workout']);
            Route::put('/workouts/{workout}/execution', [WorkoutExecutionController::class, 'update']);
            Route::get('/progress', [ProgressController::class, 'index']);
            Route::post('/progress', [ProgressController::class, 'store']);
        });

        Route::middleware('can:coach.access')->prefix('coach')->group(function (): void {
            Route::get('/home', [CoachAppController::class, 'home']);
            Route::get('/roster', [CoachAppController::class, 'roster'])->middleware('can:athletes.view');
            Route::get('/athletes/{athlete}', [CoachAppController::class, 'athlete'])->middleware('can:athletes.view');
            Route::get('/programs', [CoachAppController::class, 'programs'])->middleware('can:programs.manage');
            Route::get('/schedule', [CoachAppController::class, 'schedule'])->middleware('can:schedule.manage');
        });
    });
});
