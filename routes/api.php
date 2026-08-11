<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/status', fn () => response()->json([
        'data' => [
            'service' => 'Throughline API',
            'version' => 'v1',
            'status' => 'ok',
        ],
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
        Route::put('/profile/theme', [ProfileController::class, 'theme']);
    });
});
