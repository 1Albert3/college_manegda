<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NewsController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/enroll', [EnrollmentController::class, 'store']);

Route::get('/grades/{studentId}/{trimestre}', [GradeController::class, 'show']);
Route::get('/attendance/{studentId}', [AttendanceController::class, 'show']);
Route::get('/schedule/{studentId}', [ScheduleController::class, 'show']);
Route::get('/documents/{studentId}', [DocumentController::class, 'index']);

Route::get('/news', [NewsController::class, 'index']);
Route::get('/documents/official', [NewsController::class, 'officialDocs']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
