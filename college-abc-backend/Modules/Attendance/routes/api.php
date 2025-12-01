<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\Api\AttendanceController;
use Modules\Attendance\Http\Controllers\Api\JustificationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Attendance routes
    Route::prefix('attendances')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::post('/', [AttendanceController::class, 'store'])->name('attendances.store');
        Route::get('{uuid}', [AttendanceController::class, 'show'])->name('attendances.show');
        Route::put('{uuid}', [AttendanceController::class, 'update'])->name('attendances.update');
        Route::delete('{uuid}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

        // Specialized attendance routes
        Route::post('mark', [AttendanceController::class, 'mark']);
        Route::post('bulk-mark', [AttendanceController::class, 'bulkMark']);
        Route::get('by-student/{studentId}', [AttendanceController::class, 'byStudent']);
        Route::get('by-session/{sessionId}', [AttendanceController::class, 'bySession']);
        Route::get('by-class/{classId}', [AttendanceController::class, 'byClass']);

        // Reports and statistics
        Route::get('reports/monthly', [AttendanceController::class, 'monthlyReport']);
        Route::get('stats', [AttendanceController::class, 'stats']);
        Route::get('trends', [AttendanceController::class, 'trends']);
        Route::get('students-at-risk', [AttendanceController::class, 'studentsAtRisk']);

        // Notifications
        Route::post('send-absence-notifications', [AttendanceController::class, 'sendAbsenceNotifications']);

        // Export
        Route::get('export', [AttendanceController::class, 'export']);
    });

    // Justification routes
    Route::prefix('justifications')->group(function () {
        Route::get('/', [JustificationController::class, 'index']);
        Route::post('/', [JustificationController::class, 'store']);
        Route::get('{uuid}', [JustificationController::class, 'show']);
        Route::put('{uuid}', [JustificationController::class, 'update']);
        Route::delete('{uuid}', [JustificationController::class, 'destroy']);

        // Specialized justification routes
        Route::post('{uuid}/approve', [JustificationController::class, 'approve']);
        Route::post('{uuid}/reject', [JustificationController::class, 'reject']);
        Route::get('pending', [JustificationController::class, 'pending']);
        Route::get('by-student/{studentId}', [JustificationController::class, 'byStudent']);
        Route::get('stats', [JustificationController::class, 'stats']);

        // Document management
        Route::post('{uuid}/documents', [JustificationController::class, 'addDocument']);
        Route::delete('{uuid}/documents', [JustificationController::class, 'removeDocument']);
    });
});
