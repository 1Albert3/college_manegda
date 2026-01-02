<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\Api\AcademicYearController;
use Modules\Academic\Http\Controllers\Api\SubjectController;
use Modules\Academic\Http\Controllers\Api\ClassRoomController;
use Modules\Academic\Http\Controllers\Api\CycleController;
use Modules\Academic\Http\Controllers\Api\LevelController;
use Modules\Academic\Http\Controllers\Api\SemesterController;
use Modules\Academic\Http\Controllers\Api\ScheduleController;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // Academic Years
    Route::prefix('academic-years')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index']);
        Route::post('/', [AcademicYearController::class, 'store']);
        Route::get('/current', [AcademicYearController::class, 'current']);
        Route::get('/next', [AcademicYearController::class, 'next']);
        Route::get('/previous', [AcademicYearController::class, 'previous']);
        Route::get('/stats', [AcademicYearController::class, 'stats']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [AcademicYearController::class, 'show']);
            Route::put('/', [AcademicYearController::class, 'update']);
            Route::delete('/', [AcademicYearController::class, 'destroy']);
            Route::post('/set-current', [AcademicYearController::class, 'setCurrent']);
            Route::post('/complete', [AcademicYearController::class, 'complete']);
            Route::post('/generate-semesters', [AcademicYearController::class, 'generateSemesters']);
            Route::post('/create-from-template', [AcademicYearController::class, 'createFromTemplate']);
        });
    });

    // Subjects (Matières)
    Route::prefix('subjects')->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::post('/', [SubjectController::class, 'store']);
        Route::get('/by-category/{category}', [SubjectController::class, 'byCategory']);
        Route::get('/by-level/{level}', [SubjectController::class, 'byLevel']);
        Route::get('/grouped', [SubjectController::class, 'grouped']);
        Route::get('/stats', [SubjectController::class, 'stats']);
        Route::post('/bulk-activate', [SubjectController::class, 'bulkActivate']);
        Route::post('/bulk-deactivate', [SubjectController::class, 'bulkDeactivate']);
        Route::post('/update-coefficients', [SubjectController::class, 'updateCoefficients']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [SubjectController::class, 'show']);
            Route::put('/', [SubjectController::class, 'update']);
            Route::delete('/', [SubjectController::class, 'destroy']);
            Route::post('/assign-to-class', [SubjectController::class, 'assignToClass']);
            Route::post('/remove-from-class', [SubjectController::class, 'removeFromClass']);
            Route::post('/assign-teacher', [SubjectController::class, 'assignTeacher']);
            Route::get('/students', [SubjectController::class, 'getStudents']);
            Route::get('/average-grade/{classId}', [SubjectController::class, 'getAverageGrade']);
        });

        Route::prefix('code/{code}')->group(function () {
            Route::get('/', [SubjectController::class, 'findByCode']);
        });
    });

    // Classes (Salles de classe)
    Route::prefix('classes')->group(function () {
        Route::get('/', [ClassRoomController::class, 'index']);
        Route::post('/', [ClassRoomController::class, 'store']);
        Route::get('/by-level/{level}', [ClassRoomController::class, 'byLevel']);
        Route::get('/by-stream/{stream}', [ClassRoomController::class, 'byStream']);
        Route::get('/active', [ClassRoomController::class, 'active']);
        Route::get('/grouped', [ClassRoomController::class, 'grouped']);
        Route::get('/stats', [ClassRoomController::class, 'stats']);
        Route::post('/bulk-status-update', [ClassRoomController::class, 'bulkStatusUpdate']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [ClassRoomController::class, 'show']);
            Route::put('/', [ClassRoomController::class, 'update']);
            Route::delete('/', [ClassRoomController::class, 'destroy']);
            Route::post('/assign-subject', [ClassRoomController::class, 'assignSubject']);
            Route::post('/remove-subject', [ClassRoomController::class, 'removeSubject']);
            Route::post('/enroll-student', [ClassRoomController::class, 'enrollStudent']);
            Route::post('/update-students-count', [ClassRoomController::class, 'updateStudentsCount']);
            Route::get('/attendance-stats', [ClassRoomController::class, 'attendanceStats']);
            Route::get('/students', [ClassRoomController::class, 'students']);
            Route::get('/subjects', [ClassRoomController::class, 'subjects']);
            Route::get('/can-delete', [ClassRoomController::class, 'canDelete']);
        });

        Route::prefix('name/{name}')->group(function () {
            Route::get('/', [ClassRoomController::class, 'findByName']);
        });
    });

    // Cycles
    Route::prefix('cycles')->group(function () {
        Route::get('/', [CycleController::class, 'index']);
        Route::post('/', [CycleController::class, 'store']);
        Route::post('/reorder', [CycleController::class, 'reorder']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [CycleController::class, 'show']);
            Route::put('/', [CycleController::class, 'update']);
            Route::delete('/', [CycleController::class, 'destroy']);
            Route::post('/activate', [CycleController::class, 'activate']);
            Route::post('/deactivate', [CycleController::class, 'deactivate']);
            Route::get('/statistics', [CycleController::class, 'statistics']);
        });
    });

    // Levels (Niveaux)
    Route::prefix('levels')->group(function () {
        Route::get('/', [LevelController::class, 'index']);
        Route::post('/', [LevelController::class, 'store']);
        Route::get('/search', [LevelController::class, 'search']);
        Route::post('/reorder', [LevelController::class, 'reorder']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [LevelController::class, 'show']);
            Route::put('/', [LevelController::class, 'update']);
            Route::delete('/', [LevelController::class, 'destroy']);
            Route::post('/activate', [LevelController::class, 'activate']);
            Route::post('/deactivate', [LevelController::class, 'deactivate']);
            Route::get('/statistics', [LevelController::class, 'statistics']);
        });
    });

    // Semesters (Trimestres/Semestres)
    Route::prefix('semesters')->group(function () {
        Route::get('/', [SemesterController::class, 'index']);
        Route::post('/', [SemesterController::class, 'store']);
        Route::get('/current', [SemesterController::class, 'current']);
        Route::get('/ongoing', [SemesterController::class, 'ongoing']);
        Route::post('/generate', [SemesterController::class, 'generate']);
        Route::get('/by-year/{academicYearId}', [SemesterController::class, 'byYear']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [SemesterController::class, 'show']);
            Route::put('/', [SemesterController::class, 'update']);
            Route::delete('/', [SemesterController::class, 'destroy']);
            Route::post('/set-current', [SemesterController::class, 'setCurrent']);
        });
    });

    // Schedules (Emplois du temps)
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::post('/bulk-create', [ScheduleController::class, 'bulkCreate']);
        Route::post('/copy-to-new-year', [ScheduleController::class, 'copyToNewYear']);
        Route::get('/statistics', [ScheduleController::class, 'statistics']);
        Route::get('/class/{classRoomId}', [ScheduleController::class, 'classSchedule']);
        Route::get('/teacher/{teacherId}', [ScheduleController::class, 'teacherSchedule']);
        Route::get('/today/class/{classRoomId}', [ScheduleController::class, 'todayClass']);
        Route::get('/today/teacher/{teacherId}', [ScheduleController::class, 'todayTeacher']);

        Route::prefix('{id}')->group(function () {
            Route::get('/', [ScheduleController::class, 'show']);
            Route::put('/', [ScheduleController::class, 'update']);
            Route::delete('/', [ScheduleController::class, 'destroy']);
        });
    });
});
