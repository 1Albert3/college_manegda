<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\Api\CommunicationController;

Route::middleware(['auth:sanctum'])->prefix('v1/communication')->group(function () {
    // Send communications
    Route::post('/send', [CommunicationController::class, 'send']);
    Route::post('/send-to-user', [CommunicationController::class, 'sendToUser']);
    Route::post('/send-bulk', [CommunicationController::class, 'sendBulk']);

    // Test channels
    Route::post('/test-channel', [CommunicationController::class, 'testChannel']);

    // Communication logs and stats
    Route::get('/logs', [CommunicationController::class, 'logs']);
    Route::get('/stats', [CommunicationController::class, 'stats']);
    Route::get('/templates', [CommunicationController::class, 'templates']);

    // Retry failed communications
    Route::post('/retry-failed', [CommunicationController::class, 'retryFailed']);
});
