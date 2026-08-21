<?php

use App\Http\Controllers\Api\V1\AthleteAppController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CoachAppController;
use App\Http\Controllers\Api\V1\CoachManagementController;
use App\Http\Controllers\Api\V1\CoachReviewController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\MessagingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\ProgressPhotoController;
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
        Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
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
        Route::middleware('can:notifications.read')->group(function (): void {
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read']);
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
        });
        Route::get('/media/progress-photos/{photo}', [MediaController::class, 'progressPhoto'])->name('api.v1.media.progress-photos');

        Route::middleware('can:messages.read')->group(function (): void {
            Route::get('/messages', [MessagingController::class, 'index']);
            Route::get('/messages/{conversation}', [MessagingController::class, 'show']);
            Route::post('/messages/{conversation}', [MessagingController::class, 'store'])->middleware('can:messages.send');
            Route::get('/media/message-attachments/{media}', [MediaController::class, 'messageAttachment'])->name('api.v1.media.message-attachments');
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
            Route::get('/photos', [ProgressPhotoController::class, 'index'])->middleware('can:photos.manage');
            Route::post('/photos', [ProgressPhotoController::class, 'store'])->middleware('can:photos.manage');
            Route::delete('/photos/{photo}', [ProgressPhotoController::class, 'destroy'])->middleware('can:photos.manage');
        });

        Route::middleware('can:coach.access')->prefix('coach')->group(function (): void {
            Route::get('/home', [CoachAppController::class, 'home']);
            Route::get('/roster', [CoachAppController::class, 'roster'])->middleware('can:athletes.view');
            Route::get('/athletes/{athlete}', [CoachAppController::class, 'athlete'])->middleware('can:athletes.view');
            Route::get('/programs', [CoachAppController::class, 'programs'])->middleware('can:programs.manage');
            Route::post('/programs', [CoachManagementController::class, 'storeProgram'])->middleware('can:programs.manage');
            Route::get('/programs/{program}', [CoachManagementController::class, 'program'])->middleware('can:programs.manage');
            Route::put('/programs/{program}', [CoachManagementController::class, 'updateProgram'])->middleware('can:programs.manage');
            Route::post('/programs/{program}/duplicate', [CoachManagementController::class, 'duplicateProgram'])->middleware('can:programs.manage');
            Route::post('/programs/{program}/phases', [CoachManagementController::class, 'storePhase'])->middleware('can:programs.manage');
            Route::post('/programs/{program}/sessions', [CoachManagementController::class, 'storeSession'])->middleware('can:programs.manage');
            Route::put('/programs/{program}/sessions/{session}', [CoachManagementController::class, 'updateSession'])->middleware('can:programs.manage');
            Route::post('/programs/{program}/assignments', [CoachManagementController::class, 'assignProgram'])->middleware('can:programs.manage');
            Route::post('/assignments/{assignment}/publish', [CoachManagementController::class, 'publishAssignment'])->middleware('can:programs.manage');
            Route::patch('/assignments/{assignment}/status', [CoachManagementController::class, 'assignmentStatus'])->middleware('can:programs.manage');
            Route::get('/schedule', [CoachAppController::class, 'schedule'])->middleware('can:schedule.manage');
            Route::patch('/schedule/{workout}/reschedule', [CoachManagementController::class, 'reschedule'])->middleware('can:schedule.manage');
            Route::get('/exercises', [CoachManagementController::class, 'exercises'])->middleware('can:exercises.manage');
            Route::get('/invitations', [CoachManagementController::class, 'invitations'])->middleware('can:invitations.manage');
            Route::post('/invitations', [CoachManagementController::class, 'storeInvitation'])->middleware('can:invitations.manage');
            Route::post('/invitations/{invitation}/resend', [CoachManagementController::class, 'resendInvitation'])->middleware('can:invitations.manage');
            Route::delete('/invitations/{invitation}', [CoachManagementController::class, 'cancelInvitation'])->middleware('can:invitations.manage');
            Route::post('/athletes/{athlete}/notes', [CoachReviewController::class, 'storeNote'])->middleware('can:athletes.notes');
            Route::patch('/athletes/{athlete}/notes/{note}', [CoachReviewController::class, 'updateNote'])->middleware('can:athletes.notes');
            Route::delete('/athletes/{athlete}/notes/{note}', [CoachReviewController::class, 'destroyNote'])->middleware('can:athletes.notes');
            Route::post('/athletes/{athlete}/photos', [CoachReviewController::class, 'storePhoto'])->middleware('can:progress.review');
            Route::delete('/athletes/{athlete}/photos/{photo}', [CoachReviewController::class, 'destroyPhoto'])->middleware('can:progress.review');
        });
    });
});
