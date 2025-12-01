<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\Api\StudentController;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // Students CRUD
    Route::apiResource('students', StudentController::class);

    // Actions spécifiques
    Route::prefix('students')->group(function () {
        Route::get('matricule/{matricule}', [StudentController::class, 'findByMatricule']);
        Route::post('{student}/upload-photo', [StudentController::class, 'uploadPhoto']);
        Route::post('{student}/attach-parent', [StudentController::class, 'attachParent']);
        Route::delete('{student}/detach-parent/{parent}', [StudentController::class, 'detachParent']);
        Route::get('{student}/report-card', [StudentController::class, 'reportCard']);
        Route::post('import', [StudentController::class, 'import']);
        Route::get('export', [StudentController::class, 'export']);
        Route::get('stats', [StudentController::class, 'stats']);
    });
});
