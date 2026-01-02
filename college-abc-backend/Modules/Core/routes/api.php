<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\CoreController;

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::post('change-password', [AuthController::class, 'changePassword']);

    // Other protected resources
    Route::apiResource('cores', CoreController::class)->names('core');

    // Documents
    Route::get('documents', [\Modules\Core\Http\Controllers\DocumentController::class, 'index']);
    Route::post('documents', [\Modules\Core\Http\Controllers\DocumentController::class, 'store']);
    Route::get('documents/{id}/download', [\Modules\Core\Http\Controllers\DocumentController::class, 'download']);
    Route::delete('documents/{id}', [\Modules\Core\Http\Controllers\DocumentController::class, 'destroy']);
});
