<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Grade\Http\Controllers\Api\EvaluationController;
use Modules\Grade\Http\Controllers\Api\GradeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the ModuleServiceProvider within a group which
| is prefixed with your module alias. In this case, the alias is 'grade'.
|
| Routes follow the format: api/{module-alias}/{route}
|
*/

// Protected routes (require authentication and authorization)
Route::middleware(['api', 'auth:sanctum'])->prefix('v1')->name('grade.')->group(function () {

    // Evaluations management
    Route::prefix('evaluations')->name('evaluations.')->group(function () {
        Route::get('/', [EvaluationController::class, 'index'])->name('index');
        Route::post('/', [EvaluationController::class, 'store'])->name('store');
        Route::get('/upcoming', [EvaluationController::class, 'getUpcoming'])->name('upcoming');
        Route::post('/bulk', [EvaluationController::class, 'bulkCreate'])->name('bulk-create');

        Route::prefix('{evaluation}')->group(function () {
            Route::get('/', [EvaluationController::class, 'show'])->name('show');
            Route::put('/', [EvaluationController::class, 'update'])->name('update');
            Route::delete('/', [EvaluationController::class, 'destroy'])->name('destroy');

            // Evaluation actions
            Route::post('/start', [EvaluationController::class, 'start'])->name('start');
            Route::post('/complete', [EvaluationController::class, 'complete'])->name('complete');
            Route::delete('/cancel', [EvaluationController::class, 'cancel'])->name('cancel');

            // Reports and PDFs
            Route::get('/report', [EvaluationController::class, 'getEvaluationReport'])->name('report');
            Route::post('/pdf', [EvaluationController::class, 'generateResultPDF'])->name('pdf.generate');
            Route::get('/pdf/download', [EvaluationController::class, 'downloadResultPDF'])->name('pdf.download');
        });

        // Filtered evaluations
        Route::get('/by-teacher/{teacherId}', [EvaluationController::class, 'getByTeacher'])->name('by-teacher');
        Route::get('/by-subject/{subjectId}', [EvaluationController::class, 'getBySubject'])->name('by-subject');
        Route::get('/by-class/{classId}', [EvaluationController::class, 'getByClass'])->name('by-class');
    });

    // Grades management
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/', [GradeController::class, 'index'])->name('index');
        Route::post('/record', [GradeController::class, 'record'])->name('record');
        Route::post('/bulk-record', [GradeController::class, 'bulkRecord'])->name('bulk-record');

        // Statistics - MUST be before {grade} routes
        Route::get('/statistics', [GradeController::class, 'getStatistics'])->name('statistics');
        Route::get('/school-stats', [GradeController::class, 'getSchoolStats'])->name('school-stats');

        // Filtered grades - MUST be before {grade} routes
        Route::get('/by-student/{studentId}', [GradeController::class, 'getByStudent'])->name('by-student');
        Route::get('/by-evaluation/{evaluationId}', [GradeController::class, 'getByEvaluation'])->name('by-evaluation');
        Route::get('/absent', [GradeController::class, 'getAbsent'])->name('absent');

        // Reports - MUST be before {grade} routes
        Route::get('/student/{studentId}/report', [GradeController::class, 'getStudentReport'])->name('student-report');
        Route::get('/class/{classId}/report', [GradeController::class, 'getClassReport'])->name('class-report');
        Route::get('/teacher/{teacherId}/report', [GradeController::class, 'getTeacherReport'])->name('teacher-report');

        // PDFs
        Route::post('/student/{studentId}/report-card', [GradeController::class, 'generateReportCardPDF'])->name('report-card.generate');
        Route::get('/student/{studentId}/report-card/{academicYearId}/download', [GradeController::class, 'downloadReportCardPDF'])->name('report-card-pdf');
        Route::get('/class/{classId}/report/download', [GradeController::class, 'generateClassGradesPDF'])->name('class-report-pdf');

        // Soft delete operations
        Route::post('/{gradeId}/restore', [GradeController::class, 'restore'])->name('restore');
        Route::delete('/{gradeId}/force-delete', [GradeController::class, 'forceDelete'])->name('force-delete');

        // Dynamic {grade} routes - MUST be last
        Route::prefix('{grade}')->group(function () {
            Route::get('/', [GradeController::class, 'show'])->name('show');
            Route::put('/', [GradeController::class, 'update'])->name('update');
            Route::delete('/', [GradeController::class, 'destroy'])->name('destroy');
        });
    });
});

// Public routes (no authentication required for viewing reports if needed in the future)
// Uncomment and modify as needed for public access to certain reports
/*
Route::middleware('api')->prefix('v1/public')->name('api.grade.public.')->group(function () {
    // Public grade reports (e.g., parent access with token)
    Route::get('/grades/student/{studentId}/report', [GradeController::class, 'getStudentReport'])
         ->middleware('signed')
         ->name('grades.student-report-public');

    Route::get('/grades/student/{studentId}/report-card/download', [GradeController::class, 'downloadReportCardPDF'])
         ->middleware(['signed', 'throttle:downloads'])
         ->name('grades.report-card-pdf-public');
});
*/
