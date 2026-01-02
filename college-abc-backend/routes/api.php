<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MP\ClassMPController;
use App\Http\Controllers\MP\GradeMPController;
use App\Http\Controllers\MP\ReportCardMPController;
use App\Http\Controllers\MP\AttendanceMPController;
use App\Http\Controllers\MP\EnrollmentMPController;
use App\Http\Controllers\MP\StudentMPController;
use App\Http\Controllers\Lycee\ClassLyceeController;
use App\Http\Controllers\Lycee\GradeLyceeController;
use App\Http\Controllers\Lycee\ReportCardLyceeController;
use App\Http\Controllers\Lycee\StudentLyceeController;
use App\Http\Controllers\Lycee\EnrollmentLyceeController;
use App\Http\Controllers\Lycee\AttendanceLyceeController;
use App\Http\Controllers\College\ClassCollegeController;
use App\Http\Controllers\College\GradeCollegeController;
use App\Http\Controllers\College\ReportCardCollegeController;
use App\Http\Controllers\College\StudentCollegeController;
use App\Http\Controllers\College\EnrollmentCollegeController;
use App\Http\Controllers\College\AttendanceCollegeController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\Dashboard\DirectionDashboardController;
use App\Http\Controllers\Dashboard\TeacherDashboardController;
use App\Http\Controllers\Dashboard\ParentDashboardController;
use App\Http\Controllers\Dashboard\StudentDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Architecture Zéro Erreur - Routage par Cycle
| Authentification: Sanctum
|
*/

// V1 API
Route::prefix('v1')->group(function () {

    // --- AUTHENTIFICATION PUBLIC ---
    Route::post('auth/login', [AuthController::class, 'login']);

    // --- ROUTES PROTÉGÉES ---
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // --- UNIFIED OPERATIONS ---
        Route::controller(\App\Http\Controllers\Core\UniversalStudentController::class)->prefix('students')->group(function () {
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::get('/{id}', 'show');
        });

        // --- DASHBOARDS ---
        Route::prefix('dashboard')->group(function () {
            Route::get('direction', [DirectionDashboardController::class, 'index']);
            Route::get('direction/students', [DirectionDashboardController::class, 'getAllStudents']);
            Route::get('secretary', [DirectionDashboardController::class, 'index']); // Re-use Direction controller
            Route::get('secretary/students', [DirectionDashboardController::class, 'getAllStudents']); // Route for Secretary Students
            Route::get('direction/validations', [DirectionDashboardController::class, 'getPendingValidations']);
            Route::post('direction/validations/{id}', [DirectionDashboardController::class, 'updateValidation']);
            Route::get('teacher', [TeacherDashboardController::class, 'index']);
            Route::get('parent', [ParentDashboardController::class, 'index']);
            Route::get('student', [StudentDashboardController::class, 'index']);
            Route::get('accounting', [\App\Http\Controllers\Dashboard\AccountingDashboardController::class, 'index']);
        });

        // --- PRE-INSCRIPTION PUBLIQUE (Module 1) ---
        Route::post('enroll', [\App\Http\Controllers\EnrollmentController::class, 'store']);

        // --- MATERNELLE & PRIMAIRE (MP) ---
        Route::prefix('academic')->group(function () {
            Route::get('classrooms', [\App\Http\Controllers\AcademicController::class, 'getAllClassrooms']);
        });

        Route::prefix('mp')->group(function () {
            Route::get('classes/{class}/students', [ClassMPController::class, 'students']);
            Route::get('subjects', [GradeMPController::class, 'subjects']);
            Route::post('grades/bulk', [GradeMPController::class, 'bulkStore']);

            // Bulletins
            Route::get('report-cards/preview', [ReportCardMPController::class, 'preview']);
            Route::post('report-cards/generate', [ReportCardMPController::class, 'generate']);
            Route::post('report-cards/publish', [ReportCardMPController::class, 'publish']);
            Route::get('report-cards/download-all', [ReportCardMPController::class, 'downloadAll']);
            Route::apiResource('report-cards', ReportCardMPController::class)->only(['index', 'show', 'destroy'])->names('mp.report-cards');

            // Absences
            Route::post('attendance/bulk', [AttendanceMPController::class, 'bulkStore']);
            Route::patch('attendance/{attendance}/justify', [AttendanceMPController::class, 'justify']);
            Route::apiResource('attendance', AttendanceMPController::class)->names('mp.attendance');

            // Inscriptions & Elèves
            Route::patch('enrollments/{enrollment}/validate', [EnrollmentMPController::class, 'validate']);
            Route::patch('enrollments/{enrollment}/reject', [EnrollmentMPController::class, 'reject']);
            Route::get('enrollments/pending', [EnrollmentMPController::class, 'pending']);
            Route::apiResource('enrollments', EnrollmentMPController::class)->names('mp.enrollments');
            Route::apiResource('students', StudentMPController::class)->names('mp.students');

            Route::apiResource('classes', ClassMPController::class)->names('mp.classes');
        });

        // --- COLLÈGE ---
        Route::prefix('college')->group(function () {
            Route::get('classes/{class}/students', [ClassCollegeController::class, 'students']);

            // Bulletins
            Route::get('report-cards/preview', [ReportCardCollegeController::class, 'preview']);
            Route::post('report-cards/generate', [ReportCardCollegeController::class, 'generate']);
            Route::get('report-cards/download-all', [ReportCardCollegeController::class, 'downloadAll']);
            Route::apiResource('report-cards', ReportCardCollegeController::class)->only(['index', 'show', 'destroy'])->names('college.report-cards');

            // Absences
            Route::post('attendance/bulk', [\App\Http\Controllers\College\AttendanceCollegeController::class, 'bulkStore']);
            Route::apiResource('attendance', \App\Http\Controllers\College\AttendanceCollegeController::class)->names('college.attendance');

            // Inscriptions & Elèves
            Route::apiResource('enrollments', \App\Http\Controllers\College\EnrollmentCollegeController::class)->names('college.enrollments');
            Route::apiResource('students', \App\Http\Controllers\College\StudentCollegeController::class)->names('college.students');

            Route::apiResource('classes', ClassCollegeController::class)->names('college.classes');
            Route::get('subjects', [GradeCollegeController::class, 'subjects']);
            Route::post('grades/bulk', [GradeCollegeController::class, 'storeBulk']);
            Route::get('students/{student}/grades', [GradeCollegeController::class, 'indexStudent']);
        });

        // --- LYCÉE ---
        Route::prefix('lycee')->group(function () {
            Route::get('classes/{class}/students', [ClassLyceeController::class, 'students']);

            // Assignations Enseignants (New)
            Route::get('classes/{class}/assignments', [ClassLyceeController::class, 'assignments']);
            Route::post('classes/{class}/assignments', [ClassLyceeController::class, 'assignTeacher']);
            Route::delete('classes/{class}/assignments/{assignment}', [ClassLyceeController::class, 'removeAssignment']);
            Route::get('resources/available', [ClassLyceeController::class, 'availableResources']);

            // Bulletins
            Route::get('report-cards/preview', [ReportCardLyceeController::class, 'preview']);
            Route::post('report-cards/generate', [ReportCardLyceeController::class, 'generate']);
            Route::post('report-cards/generate-class', [ReportCardLyceeController::class, 'generateForClass']);
            Route::get('report-cards/download-all', [ReportCardLyceeController::class, 'downloadAll']);
            Route::apiResource('report-cards', ReportCardLyceeController::class)->only(['index', 'show', 'destroy'])->names('lycee.report-cards');

            // Absences
            Route::post('attendance/bulk', [\App\Http\Controllers\Lycee\AttendanceLyceeController::class, 'bulkStore']);
            Route::apiResource('attendance', \App\Http\Controllers\Lycee\AttendanceLyceeController::class)->names('lycee.attendance');

            // Inscriptions & Elèves
            Route::apiResource('enrollments', \App\Http\Controllers\Lycee\EnrollmentLyceeController::class)->names('lycee.enrollments');
            Route::apiResource('students', \App\Http\Controllers\Lycee\StudentLyceeController::class)->names('lycee.students');

            Route::apiResource('classes', ClassLyceeController::class)->names('lycee.classes');
            Route::get('subjects', [GradeLyceeController::class, 'subjects']);
            Route::post('grades/bulk', [GradeLyceeController::class, 'storeBulk']);
            Route::get('students/{student}/grades', [GradeLyceeController::class, 'indexStudent']);
        });

        // --- FINANCE ---
        Route::prefix('finance')->group(function () {
            // Factures
            Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print']);
            Route::apiResource('invoices', InvoiceController::class);

            // Paiements
            Route::get('payments/stats', [PaymentController::class, 'stats']);
            Route::get('payments/unpaid', [PaymentController::class, 'unpaid']);
            Route::post('payments/reminders', [PaymentController::class, 'sendReminders']);
            Route::patch('payments/{payment}/validate', [PaymentController::class, 'validate']);
            Route::patch('payments/{payment}/reject', [PaymentController::class, 'reject']);
            Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel']);
            Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt']);
            Route::apiResource('payments', PaymentController::class);

            // Types de frais / Structures
            Route::apiResource('fee-types', FeeTypeController::class);
        });

        // --- CORE & ADMINISTRATION ---
        Route::prefix('core')->group(function () {
            Route::get('school-years/current', [\App\Http\Controllers\Core\SchoolYearController::class, 'current']);
            Route::apiResource('school-years', \App\Http\Controllers\Core\SchoolYearController::class);

            Route::get('users/stats', [UserController::class, 'stats']);
            Route::apiResource('users', UserController::class);
        });

        // --- COMMUN (Emplois du temps, bibliothèque...) ---
        Route::get('schedules/class/{classRoomId}', [\App\Http\Controllers\Academic\ScheduleController::class, 'byClass']);
        Route::get('schedules/teacher/{teacherId}', [\App\Http\Controllers\Academic\ScheduleController::class, 'byTeacher']);
        Route::post('schedules/generate', [\App\Http\Controllers\Academic\ScheduleController::class, 'generate']);
        Route::get('schedules/time-slots', [\App\Http\Controllers\Academic\ScheduleController::class, 'getTimeSlots']);
        Route::apiResource('schedules', \App\Http\Controllers\Academic\ScheduleController::class);

        Route::post('library/loan', [LibraryController::class, 'loan']);
        Route::post('library/return/{loan}', [LibraryController::class, 'returnBook']);
        Route::apiResource('library', LibraryController::class);
    });
});
