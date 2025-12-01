# 🏗️ ARCHITECTURE REST API - COLLÈGE ABC
## Laravel + nwidart/laravel-modules

---

## 📦 INSTALLATION & CONFIGURATION

```bash
# Créer le projet Laravel
composer create-project laravel/laravel college-abc-api
cd college-abc-api

# Installer nwidart/laravel-modules
composer require nwidart/laravel-modules

# Publier la config
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"

# Packages essentiels
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require spatie/laravel-query-builder
composer require spatie/laravel-activitylog
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require predis/predis
```

---

## 🗂️ STRUCTURE DES MODULES

```
Modules/
├── Core/                    # Module central (Users, Auth, Roles)
├── Student/                 # Gestion élèves
├── Teacher/                 # Gestion enseignants
├── Academic/                # Classes, Matières, Emplois du temps
├── Grade/                   # Notes, Bulletins, Évaluations
├── Finance/                 # Paiements, Factures, Comptabilité
├── Attendance/              # Présences, Absences, Pointages
├── Communication/           # SMS, Email, Notifications
├── Service/                 # Cantine, Transport, Santé
├── Document/                # Gestion documentaire
├── Elearning/               # Cours en ligne, Devoirs
├── Alumni/                  # Anciens élèves
└── Report/                  # Statistiques, Tableaux de bord
```

---

## 🎯 CRÉATION DES MODULES

```bash
# Créer tous les modules
php artisan module:make Core
php artisan module:make Student
php artisan module:make Teacher
php artisan module:make Academic
php artisan module:make Grade
php artisan module:make Finance
php artisan module:make Attendance
php artisan module:make Communication
php artisan module:make Service
php artisan module:make Document
php artisan module:make Elearning
php artisan module:make Alumni
php artisan module:make Report
```

---

## 📐 STRUCTURE TYPE D'UN MODULE

```
Modules/Student/
├── Config/
│   └── config.php
├── Database/
│   ├── Migrations/
│   │   ├── 2024_01_01_000001_create_students_table.php
│   │   ├── 2024_01_01_000002_create_parent_student_table.php
│   │   └── 2024_01_01_000003_create_enrollments_table.php
│   ├── Seeders/
│   │   └── StudentDatabaseSeeder.php
│   └── factories/
│       └── StudentFactory.php
├── Entities/
│   ├── Student.php
│   ├── ParentStudent.php
│   └── Enrollment.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── StudentController.php
│   │       ├── EnrollmentController.php
│   │       └── StudentAttendanceController.php
│   ├── Middleware/
│   ├── Requests/
│   │   ├── StoreStudentRequest.php
│   │   ├── UpdateStudentRequest.php
│   │   └── EnrollStudentRequest.php
│   └── Resources/
│       ├── StudentResource.php
│       ├── StudentCollection.php
│       └── EnrollmentResource.php
├── Repositories/
│   ├── Contracts/
│   │   └── StudentRepositoryInterface.php
│   └── StudentRepository.php
├── Services/
│   ├── StudentService.php
│   ├── EnrollmentService.php
│   └── StudentExportService.php
├── Transformers/
│   └── StudentTransformer.php
├── Routes/
│   ├── api.php
│   └── web.php
├── Tests/
│   ├── Feature/
│   │   └── StudentApiTest.php
│   └── Unit/
│       └── StudentServiceTest.php
├── Providers/
│   ├── StudentServiceProvider.php
│   └── RouteServiceProvider.php
└── module.json
```

---

## 🔐 MODULE CORE - AUTHENTIFICATION & USERS

### **Entities/User.php**
```php
<?php

namespace Modules\Core\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 
        'role_type', 'is_active', 'last_login_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relations polymorphiques
    public function profile()
    {
        return $this->morphTo('profile', 'profile_type', 'profile_id');
    }
}
```

### **Routes/api.php (Core)**
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::get('messages/sent', [MessageController::class, 'sent']);
});
```

---

## 📚 MODULE ELEARNING - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Courses
    Route::apiResource('courses', CourseController::class);
    Route::get('courses/subject/{subject}', [CourseController::class, 'bySubject']);
    Route::get('courses/class/{class}', [CourseController::class, 'byClass']);
    Route::post('courses/{course}/upload-material', [CourseController::class, 'uploadMaterial']);
    
    // Assignments
    Route::apiResource('assignments', AssignmentController::class);
    Route::get('assignments/course/{course}', [AssignmentController::class, 'byCourse']);
    Route::get('assignments/student/{student}', [AssignmentController::class, 'byStudent']);
    
    // Submissions
    Route::post('submissions/submit', [SubmissionController::class, 'submit']);
    Route::get('submissions/assignment/{assignment}', [SubmissionController::class, 'byAssignment']);
    Route::post('submissions/{submission}/grade', [SubmissionController::class, 'grade']);
    Route::get('submissions/{submission}/download', [SubmissionController::class, 'download']);
    
    // Quiz
    Route::apiResource('quizzes', QuizController::class);
    Route::post('quizzes/{quiz}/attempt', [QuizController::class, 'attempt']);
    Route::get('quizzes/{quiz}/results', [QuizController::class, 'results']);
});
```

---

## 📋 MODULE REPORT - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard
    Route::get('reports/dashboard', [DashboardController::class, 'index']);
    Route::get('reports/dashboard/director', [DashboardController::class, 'director']);
    Route::get('reports/dashboard/teacher', [DashboardController::class, 'teacher']);
    Route::get('reports/dashboard/parent', [DashboardController::class, 'parent']);
    
    // Statistics
    Route::get('reports/statistics/students', [StatisticsController::class, 'students']);
    Route::get('reports/statistics/attendance', [StatisticsController::class, 'attendance']);
    Route::get('reports/statistics/grades', [StatisticsController::class, 'grades']);
    Route::get('reports/statistics/finance', [StatisticsController::class, 'finance']);
    
    // Analytics
    Route::get('reports/analytics/performance', [AnalyticsController::class, 'performance']);
    Route::get('reports/analytics/trends', [AnalyticsController::class, 'trends']);
    Route::get('reports/analytics/comparison', [AnalyticsController::class, 'comparison']);
    
    // Exports
    Route::get('reports/export/students', [ReportExportController::class, 'students']);
    Route::get('reports/export/grades', [ReportExportController::class, 'grades']);
    Route::get('reports/export/attendance', [ReportExportController::class, 'attendance']);
    Route::get('reports/export/finance', [ReportExportController::class, 'finance']);
});
```

---

## 🚌 MODULE SERVICE - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Cantine
    Route::apiResource('canteen/subscriptions', CanteenSubscriptionController::class);
    Route::apiResource('canteen/menus', CanteenMenuController::class);
    Route::get('canteen/student/{student}', [CanteenSubscriptionController::class, 'byStudent']);
    
    // Transport
    Route::apiResource('transport/routes', TransportRouteController::class);
    Route::apiResource('transport/subscriptions', TransportSubscriptionController::class);
    Route::get('transport/student/{student}', [TransportSubscriptionController::class, 'byStudent']);
    Route::get('transport/routes/{route}/students', [TransportRouteController::class, 'students']);
    
    // Santé
    Route::apiResource('health/records', HealthRecordController::class);
    Route::get('health/student/{student}', [HealthRecordController::class, 'byStudent']);
    Route::post('health/student/{student}/visit', [HealthRecordController::class, 'recordVisit']);
});
```

---

## 🎓 MODULE ALUMNI - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Alumni
    Route::apiResource('alumni', AlumniController::class);
    Route::get('alumni/search', [AlumniController::class, 'search']);
    Route::get('alumni/{alumni}/profile', [AlumniController::class, 'profile']);
    Route::put('alumni/{alumni}/update-career', [AlumniController::class, 'updateCareer']);
    
    // Networking
    Route::post('alumni/connect', [AlumniNetworkController::class, 'connect']);
    Route::get('alumni/connections', [AlumniNetworkController::class, 'connections']);
    
    // Events
    Route::apiResource('alumni/events', AlumniEventController::class);
    Route::post('alumni/events/{event}/register', [AlumniEventController::class, 'register']);
});
```

---

## 📄 MODULE DOCUMENT - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Documents
    Route::apiResource('documents', DocumentController::class);
    Route::post('documents/upload', [DocumentController::class, 'upload']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('documents/category/{category}', [DocumentController::class, 'byCategory']);
    
    // Règlements & Circulaires
    Route::get('documents/regulations', [DocumentController::class, 'regulations']);
    Route::get('documents/circulars', [DocumentController::class, 'circulars']);
    
    // Formulaires
    Route::get('documents/forms', [DocumentController::class, 'forms']);
});
```

---

## 🔧 CONFIGURATION GLOBALE

### **config/modules.php**
```php
<?php

return [
    'namespace' => 'Modules',
    'stubs' => [
        'enabled' => false,
        'path' => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
    ],
    'paths' => [
        'modules' => base_path('Modules'),
        'assets' => public_path('modules'),
        'migration' => base_path('database/migrations'),
    ],
    'scan' => [
        'enabled' => false,
        'paths' => [],
    ],
    'composer' => [
        'vendor' => 'nwidart',
        'author' => [
            'name' => 'Your Name',
            'email' => 'email@example.com',
        ],
    ],
    'cache' => [
        'enabled' => false,
        'key' => 'laravel-modules',
        'lifetime' => 60,
    ],
    'register' => [
        'translations' => true,
    ],
];
```

### **config/sanctum.php (Configuration API)**
```php
<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    'guard' => ['web'],

    'expiration' => null, // Tokens ne expirent pas par défaut

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],
];
```

### **config/cors.php**
```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // À restreindre en production

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

### **.env Configuration**
```env
APP_NAME="Collège ABC API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=college_abc
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# SMS Configuration (exemple avec Twilio)
SMS_DRIVER=twilio
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=

# Payment Gateway (exemple)
PAYMENT_GATEWAY=cinetpay
CINETPAY_API_KEY=
CINETPAY_SITE_ID=

# File Storage
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

---

## 🛡️ MIDDLEWARE PERSONNALISÉS

### **app/Http/Middleware/CheckRole.php**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user() || !$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.'
            ], 403);
        }

        return $next($request);
    }
}
```

### **app/Http/Middleware/ApiLogger.php**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiLogger
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        Log::info('API Request', [
            'user_id' => $request->user()?->id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'status' => $response->status(),
            'timestamp' => now(),
        ]);

        return $response;
    }
}
```

### **Enregistrement dans app/Http/Kernel.php**
```php
protected $middlewareAliases = [
    // ... autres middlewares
    'role' => \App\Http\Middleware\CheckRole::class,
    'api.logger' => \App\Http\Middleware\ApiLogger::class,
];
```

---

## 📝 TRAITS RÉUTILISABLES

### **app/Traits/HasUuid.php**
```php
<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
```

### **app/Traits/Searchable.php**
```php
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearch(Builder $query, ?string $search, array $columns = [])
    {
        if (empty($search)) {
            return $query;
        }

        $searchColumns = !empty($columns) ? $columns : $this->searchable ?? [];

        return $query->where(function ($q) use ($search, $searchColumns) {
            foreach ($searchColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }
}
```

---

## 🎯 EXEMPLE DE SERVICE COMPLET (AttendanceService)

### **Modules/Attendance/Services/AttendanceService.php**
```php
<?php

namespace Modules\Attendance\Services;

use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Entities\Attendance;
use Modules\Communication\Services\SMSService;
use Modules\Student\Entities\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(
        private AttendanceRepository $repository,
        private SMSService $smsService
    ) {}

    public function markAttendance(int $studentId, string $date, string $status, array $data = []): Attendance
    {
        return DB::transaction(function () use ($studentId, $date, $status, $data) {
            $attendance = $this->repository->updateOrCreate(
                [
                    'student_id' => $studentId,
                    'date' => $date,
                ],
                [
                    'status' => $status,
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                    'notes' => $data['notes'] ?? null,
                    'period' => $data['period'] ?? 'full_day',
                ]
            );

            // Envoyer SMS si absent
            if ($status === 'absent') {
                $this->notifyParentAbsence($studentId, $date);
            }

            return $attendance;
        });
    }

    public function bulkMarkAttendance(int $classId, string $date, array $attendances): array
    {
        $results = [];

        DB::transaction(function () use ($classId, $date, $attendances, &$results) {
            foreach ($attendances as $attendance) {
                $results[] = $this->markAttendance(
                    $attendance['student_id'],
                    $date,
                    $attendance['status'],
                    $attendance
                );
            }
        });

        return $results;
    }

    public function getStudentAttendance(int $studentId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getByStudent(
            $studentId,
            $startDate ?? Carbon::now()->startOfMonth()->toDateString(),
            $endDate ?? Carbon::now()->endOfMonth()->toDateString()
        );
    }

    public function getClassAttendance(int $classId, string $date)
    {
        return $this->repository->getByClassAndDate($classId, $date);
    }

    public function calculateAttendanceRate(int $studentId, ?string $startDate = null, ?string $endDate = null): float
    {
        $attendances = $this->getStudentAttendance($studentId, $startDate, $endDate);
        
        if ($attendances->isEmpty()) {
            return 0;
        }

        $presentCount = $attendances->where('status', 'present')->count();
        $totalCount = $attendances->count();

        return round(($presentCount / $totalCount) * 100, 2);
    }

    public function getMonthlyReport(int $classId, int $month, int $year)
    {
        $students = Student::byClass($classId)->get();
        
        return $students->map(function ($student) use ($month, $year) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
            
            $attendances = $this->getStudentAttendance($student->id, $startDate, $endDate);
            
            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'matricule' => $student->matricule,
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'excused' => $attendances->where('status', 'excused')->count(),
                'attendance_rate' => $this->calculateAttendanceRate($student->id, $startDate, $endDate),
            ];
        });
    }

    private function notifyParentAbsence(int $studentId, string $date): void
    {
        $student = Student::with('parents')->find($studentId);
        
        if (!$student || $student->parents->isEmpty()) {
            return;
        }

        $primaryParent = $student->parents->firstWhere('pivot.is_primary', true) 
                        ?? $student->parents->first();

        if ($primaryParent && $primaryParent->phone) {
            $message = sprintf(
                "Cher parent, votre enfant %s (%s) a été marqué(e) absent(e) le %s. Collège ABC",
                $student->full_name,
                $student->matricule,
                Carbon::parse($date)->format('d/m/Y')
            );

            $this->smsService->send($primaryParent->phone, $message);
        }
    }

    public function sendBulkAbsenceNotifications(string $date): int
    {
        $absences = $this->repository->getAbsencesByDate($date);
        $sentCount = 0;

        foreach ($absences as $absence) {
            try {
                $this->notifyParentAbsence($absence->student_id, $date);
                $sentCount++;
            } catch (\Exception $e) {
                \Log::error("Failed to send absence notification", [
                    'student_id' => $absence->student_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $sentCount;
    }
}
```

---

## 🔄 JOBS (FILES D'ATTENTE)

### **Modules/Communication/Jobs/SendBulkSMS.php**
```php
<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Communication\Services\SMSService;

class SendBulkSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        private array $recipients,
        private string $message
    ) {}

    public function handle(SMSService $smsService)
    {
        foreach ($this->recipients as $recipient) {
            try {
                $smsService->send($recipient['phone'], $this->message);
            } catch (\Exception $e) {
                \Log::error("Failed to send SMS to {$recipient['phone']}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

### **Modules/Grade/Jobs/GenerateBulletins.php**
```php
<?php

namespace Modules\Grade\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Grade\Services\BulletinService;

class GenerateBulletins implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $classId,
        private int $term,
        private int $academicYearId
    ) {}

    public function handle(BulletinService $bulletinService)
    {
        $bulletinService->generateForClass(
            $this->classId,
            $this->term,
            $this->academicYearId
        );
    }
}
```

---

## 🧪 TESTS UNITAIRES

### **Modules/Student/Tests/Unit/StudentServiceTest.php**
```php
<?php

namespace Modules\Student\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Student\Services\StudentService;
use Modules\Student\Entities\Student;
use Modules\Core\Entities\User;

class StudentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StudentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StudentService::class);
    }

    /** @test */
    public function it_can_create_a_student()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-15',
            'gender' => 'M',
            'email' => 'john.doe@example.com',
        ];

        $student = $this->service->createStudent($data);

        $this->assertInstanceOf(Student::class, $student);
        $this->assertEquals('John', $student->first_name);
        $this->assertNotNull($student->matricule);
        $this->assertNotNull($student->user_id);
    }

    /** @test */
    public function it_generates_unique_matricule()
    {
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();

        $this->assertNotEquals($student1->matricule, $student2->matricule);
    }

    /** @test */
    public function it_can_attach_parent_to_student()
    {
        $student = Student::factory()->create();
        $parent = User::factory()->create(['role_type' => 'parent']);

        $this->service->attachParent($student->id, $parent->id, 'father', true);

        $this->assertTrue($student->parents()->where('users.id', $parent->id)->exists());
    }
}
```

---

## 📊 RESPONSE HELPERS

### **app/Http/Responses/ApiResponse.php**
```php
<?php

namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function paginated($data, string $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
        ]);
    }
}
```

---

## 🚀 COMMANDES ARTISAN PERSONNALISÉES

### **Installation complète du système**
```bash
php artisan app:install
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallSystem extends Command
{
    protected $signature = 'app:install';
    protected $description = 'Install the complete system';

    public function handle()
    {
        $this->info('🚀 Installing Collège ABC System...');

        // Migrations
        $this->call('migrate:fresh');
        
        // Seeders
        $this->call('db:seed');
        
        // Permissions
        $this->call('permission:create-roles');
        
        // Storage link
        $this->call('storage:link');
        
        // Clear cache
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        
        $this->info('✅ System installed successfully!');
    }
}
```

---

## 📋 PLAN D'EXÉCUTION (ROADMAP)

### **SEMAINE 1-2 : Configuration & Core**
- [ ] Setup Laravel + Modules
- [ ] Configuration de l'environnement
- [ ] Module Core (Auth, Users, Roles)
- [ ] Tests d'authentification API
- [ ] Documentation Postman/Swagger

### **SEMAINE 3-4 : Modules Académiques**
- [ ] Module Student (CRUD complet)
- [ ] Module Teacher
- [ ] Module Academic (Classes, Matières)
- [ ] Module Timetable

### **SEMAINE 5-6 : Notes & Finance**
- [ ] Module Grade
- [ ] Génération bulletins PDF
- [ ] Module Finance (Paiements)
- [ ] Intégration gateway paiement

### **SEMAINE 7-8 : Présence & Communication**
- [ ] Module Attendance
- [ ] Notifications SMS automatiques
- [ ] Module Communication
- [ ] Emails & Messages internes

### **SEMAINE 9-10 : Services & E-learning**
- [ ] Module Service (Cantine, Transport)
- [ ] Module Elearning
- [ ] Module Document
- [ ] Module Alumni

### **SEMAINE 11-12 : Rapports & Finalisation**
- [ ] Module Report
- [ ] Tableaux de bord
- [ ] Tests complets
- [ ] Documentation finale
- [ ] Déploiement

---

## ✅ CHECKLIST AVANT DÉPLOIEMENT

### **Sécurité**
- [ ] Toutes les routes protégées par auth:sanctum
- [ ] Permissions vérifiées sur chaque endpoint
- [ ] Rate limiting configuré
- [ ] CORS configuré correctement
- [ ] Variables sensibles dans .env
- [ ] Validation stricte sur tous les inputs

### **Performance**
- [ ] Eager loading N+1 queries
- [ ] Index database sur colonnes fréquentes
- [ ] Redis configuré pour cache/sessions
- [ ] Queue workers configurés
- [ ] Pagination sur toutes les listes

### **Tests**
- [ ] Tests unitaires modules critiques
- [ ] Tests d'intégration API
- [ ] Tests de permission/autorisation
- [ ] Tests de validation

### **Documentation**
- [ ] Collection Postman complète
- [ ] README détaillé
- [ ] Documentation API (Swagger/OpenAPI)
- [ ] Guide déploiement

---

## 📞 INTÉGRATIONS TIERCES RECOMMANDÉES

### **SMS (Burkina Faso)**
- **Twilio** (international)
- **Africa's Talking** (Afrique)
- **Hubtel** (West Africa)

### **Paiement Mobile Money**
- **CinetPay** (recommandé pour BF)
- **Wave** 
- **Orange Money API**
- **Moov Money API**

### **Stockage Fichiers**
- **AWS S3** / **DigitalOcean Spaces**
- **Local** (développement)

---

## 🎯 PROCHAINES ÉTAPES

**Par où voulez-vous commencer ?**

1. Configuration détaillée d'un module spécifique ?
2. Exemples de migrations complètes ?
3. Structure des Repositories ?
4. Configuration CI/CD ?
5. Exemples de tests ?
6. Documentation Postman ?

**Dites-moi et je développe en détail ! 🚀**
});
```

### **Controllers/Api/AuthController.php**
```php
<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Requests\LoginRequest;
use Modules\Core\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'abilities' => $result['abilities']
            ]
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load('roles', 'permissions')
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
```

---

## 👨‍🎓 MODULE STUDENT - EXEMPLE COMPLET

### **Database/Migrations/create_students_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['M', 'F']);
            $table->string('place_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['active', 'suspended', 'graduated', 'withdrawn'])->default('active');
            $table->json('medical_info')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('matricule');
            $table->index('status');
        });

        // Table pivot parents-élèves
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('relationship', ['father', 'mother', 'guardian', 'other']);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
        });

        // Inscriptions
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->date('enrollment_date');
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('students');
    }
};
```

### **Entities/Student.php**
```php
<?php

namespace Modules\Student\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Entities\User;
use Modules\Academic\Entities\ClassRoom;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'matricule', 'first_name', 'last_name',
        'date_of_birth', 'gender', 'place_of_birth', 
        'address', 'photo', 'status', 'medical_info'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'medical_info' => 'array',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
                    ->withPivot('relationship', 'is_primary')
                    ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)
                    ->whereHas('academicYear', fn($q) => $q->where('is_current', true));
    }

    public function attendances()
    {
        return $this->hasMany(\Modules\Attendance\Entities\Attendance::class);
    }

    public function grades()
    {
        return $this->hasMany(\Modules\Grade\Entities\Grade::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByClass($query, $classId)
    {
        return $query->whereHas('currentEnrollment', fn($q) => $q->where('class_id', $classId));
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }
}
```

### **Routes/api.php (Student Module)**
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\Api\StudentController;
use Modules\Student\Http\Controllers\Api\EnrollmentController;

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
    });

    // Enrollments
    Route::prefix('enrollments')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index']);
        Route::post('/', [EnrollmentController::class, 'store']);
        Route::put('{enrollment}', [EnrollmentController::class, 'update']);
        Route::delete('{enrollment}', [EnrollmentController::class, 'destroy']);
        Route::get('by-class/{class}', [EnrollmentController::class, 'byClass']);
        Route::get('by-year/{year}', [EnrollmentController::class, 'byYear']);
    });
});
```

### **Http/Controllers/Api/StudentController.php**
```php
<?php

namespace Modules\Student\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Student\Services\StudentService;
use Modules\Student\Http\Requests\StoreStudentRequest;
use Modules\Student\Http\Requests\UpdateStudentRequest;
use Modules\Student\Http\Resources\StudentResource;
use Modules\Student\Http\Resources\StudentCollection;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class StudentController extends Controller
{
    public function __construct(
        private StudentService $studentService
    ) {
        $this->middleware('permission:view-students')->only(['index', 'show']);
        $this->middleware('permission:create-students')->only('store');
        $this->middleware('permission:update-students')->only('update');
        $this->middleware('permission:delete-students')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $students = QueryBuilder::for($this->studentService->getStudentsQuery())
            ->allowedFilters([
                'matricule',
                'first_name',
                'last_name',
                'status',
                AllowedFilter::exact('gender'),
                AllowedFilter::scope('by_class'),
            ])
            ->allowedIncludes(['user', 'currentEnrollment.class', 'parents'])
            ->allowedSorts(['created_at', 'first_name', 'last_name', 'matricule'])
            ->paginate($request->get('per_page', 15));

        return response()->json(new StudentCollection($students));
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->createStudent($request->validated());

        return response()->json([
            'message' => 'Student created successfully',
            'data' => new StudentResource($student)
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $student = $this->studentService->findStudent($id);

        return response()->json([
            'data' => new StudentResource($student->load([
                'user', 'parents', 'currentEnrollment.class', 
                'attendances' => fn($q) => $q->latest()->limit(10)
            ]))
        ]);
    }

    public function update(UpdateStudentRequest $request, int $id): JsonResponse
    {
        $student = $this->studentService->updateStudent($id, $request->validated());

        return response()->json([
            'message' => 'Student updated successfully',
            'data' => new StudentResource($student)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->studentService->deleteStudent($id);

        return response()->json([
            'message' => 'Student deleted successfully'
        ], 204);
    }

    public function findByMatricule(string $matricule): JsonResponse
    {
        $student = $this->studentService->findByMatricule($matricule);

        return response()->json([
            'data' => new StudentResource($student)
        ]);
    }

    public function attachParent(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'parent_id' => 'required|exists:users,id',
            'relationship' => 'required|in:father,mother,guardian,other',
            'is_primary' => 'boolean'
        ]);

        $this->studentService->attachParent(
            $id, 
            $request->parent_id, 
            $request->relationship,
            $request->is_primary ?? false
        );

        return response()->json([
            'message' => 'Parent attached successfully'
        ]);
    }

    public function export(Request $request)
    {
        return $this->studentService->exportStudents($request->all());
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        $result = $this->studentService->importStudents($request->file('file'));

        return response()->json([
            'message' => 'Import completed',
            'data' => $result
        ]);
    }
}
```

### **Services/StudentService.php**
```php
<?php

namespace Modules\Student\Services;

use Modules\Student\Repositories\StudentRepository;
use Modules\Student\Entities\Student;
use Modules\Core\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentService
{
    public function __construct(
        private StudentRepository $repository,
        private UserService $userService
    ) {}

    public function getStudentsQuery()
    {
        return $this->repository->query();
    }

    public function createStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            // Créer le user associé
            $userData = [
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => bcrypt($data['password'] ?? Str::random(10)),
                'role_type' => 'student',
            ];
            
            $user = $this->userService->createUser($userData);
            $user->assignRole('student');

            // Générer matricule si non fourni
            if (!isset($data['matricule'])) {
                $data['matricule'] = $this->generateMatricule();
            }

            $data['user_id'] = $user->id;

            $student = $this->repository->create($data);

            // Attacher les parents si fournis
            if (isset($data['parents'])) {
                foreach ($data['parents'] as $parentData) {
                    $this->attachParent(
                        $student->id,
                        $parentData['parent_id'],
                        $parentData['relationship'],
                        $parentData['is_primary'] ?? false
                    );
                }
            }

            return $student->fresh();
        });
    }

    public function updateStudent(int $id, array $data): Student
    {
        return DB::transaction(function () use ($id, $data) {
            $student = $this->findStudent($id);
            
            // Mise à jour du user si nécessaire
            if (isset($data['email']) || isset($data['phone'])) {
                $student->user->update([
                    'email' => $data['email'] ?? $student->user->email,
                    'phone' => $data['phone'] ?? $student->user->phone,
                ]);
            }

            $student->update($data);
            
            return $student->fresh();
        });
    }

    public function deleteStudent(int $id): bool
    {
        $student = $this->findStudent($id);
        return $student->delete();
    }

    public function findStudent(int $id): Student
    {
        return $this->repository->findOrFail($id);
    }

    public function findByMatricule(string $matricule): Student
    {
        return $this->repository->findByMatricule($matricule);
    }

    public function attachParent(int $studentId, int $parentId, string $relationship, bool $isPrimary = false): void
    {
        $student = $this->findStudent($studentId);
        
        $student->parents()->syncWithoutDetaching([
            $parentId => [
                'relationship' => $relationship,
                'is_primary' => $isPrimary
            ]
        ]);
    }

    private function generateMatricule(): string
    {
        $year = date('Y');
        $count = Student::whereYear('created_at', $year)->count() + 1;
        
        return sprintf('STU%s%04d', $year, $count);
    }

    public function exportStudents(array $filters)
    {
        // Utiliser maatwebsite/excel
        return Excel::download(new StudentsExport($filters), 'students.xlsx');
    }

    public function importStudents($file)
    {
        // Logique d'import
        return Excel::import(new StudentsImport, $file);
    }
}
```

### **Http/Resources/StudentResource.php**
```php
<?php

namespace Modules\Student\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
            'age' => $this->age,
            'gender' => $this->gender,
            'place_of_birth' => $this->place_of_birth,
            'address' => $this->address,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'status' => $this->status,
            'medical_info' => $this->medical_info,
            'user' => [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ],
            'current_class' => $this->whenLoaded('currentEnrollment', function () {
                return $this->currentEnrollment ? [
                    'id' => $this->currentEnrollment->class->id,
                    'name' => $this->currentEnrollment->class->name,
                ] : null;
            }),
            'parents' => $this->whenLoaded('parents', function () {
                return $this->parents->map(function ($parent) {
                    return [
                        'id' => $parent->id,
                        'name' => $parent->name,
                        'phone' => $parent->phone,
                        'email' => $parent->email,
                        'relationship' => $parent->pivot->relationship,
                        'is_primary' => $parent->pivot->is_primary,
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

---

## 🎓 MODULE ACADEMIC - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Academic Years
    Route::apiResource('academic-years', AcademicYearController::class);
    Route::post('academic-years/{year}/set-current', [AcademicYearController::class, 'setCurrent']);
    
    // Classes
    Route::apiResource('classes', ClassController::class);
    Route::get('classes/{class}/students', [ClassController::class, 'students']);
    Route::get('classes/{class}/timetable', [ClassController::class, 'timetable']);
    
    // Subjects
    Route::apiResource('subjects', SubjectController::class);
    
    // Timetables
    Route::apiResource('timetables', TimetableController::class);
    Route::get('timetables/class/{class}', [TimetableController::class, 'byClass']);
    Route::get('timetables/teacher/{teacher}', [TimetableController::class, 'byTeacher']);
    Route::post('timetables/bulk-create', [TimetableController::class, 'bulkCreate']);
});
```

---

## 📊 MODULE GRADE - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Grades
    Route::apiResource('grades', GradeController::class);
    Route::post('grades/bulk-entry', [GradeController::class, 'bulkEntry']);
    Route::get('grades/student/{student}', [GradeController::class, 'byStudent']);
    Route::get('grades/class/{class}/subject/{subject}', [GradeController::class, 'byClassSubject']);
    
    // Bulletins
    Route::get('bulletins/student/{student}/term/{term}', [BulletinController::class, 'generate']);
    Route::post('bulletins/class/{class}/term/{term}/generate-all', [BulletinController::class, 'generateForClass']);
    Route::get('bulletins/{bulletin}/download', [BulletinController::class, 'download']);
});
```

---

## 💰 MODULE FINANCE - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Fee Structures
    Route::apiResource('fee-structures', FeeStructureController::class);
    
    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/record', [PaymentController::class, 'recordPayment']);
    Route::get('payments/student/{student}', [PaymentController::class, 'byStudent']);
    Route::get('payments/pending', [PaymentController::class, 'pending']);
    Route::post('payments/{payment}/send-receipt', [PaymentController::class, 'sendReceipt']);
    
    // Invoices
    Route::get('invoices/student/{student}', [InvoiceController::class, 'byStudent']);
    Route::post('invoices/generate', [InvoiceController::class, 'generate']);
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download']);
    
    // Reports
    Route::get('finance/reports/summary', [FinanceReportController::class, 'summary']);
    Route::get('finance/reports/debts', [FinanceReportController::class, 'debts']);
    Route::get('finance/reports/revenue', [FinanceReportController::class, 'revenue']);
});
```

---

## ✅ MODULE ATTENDANCE - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Attendance
    Route::post('attendances/mark', [AttendanceController::class, 'mark']);
    Route::post('attendances/bulk-mark', [AttendanceController::class, 'bulkMark']);
    Route::get('attendances/class/{class}/date/{date}', [AttendanceController::class, 'byClassDate']);
    Route::get('attendances/student/{student}', [AttendanceController::class, 'byStudent']);
    Route::get('attendances/reports/monthly', [AttendanceController::class, 'monthlyReport']);
    
    // Notifications automatiques
    Route::post('attendances/send-absence-notifications', [AttendanceController::class, 'sendAbsenceNotifications']);
});
```

---

## 📱 MODULE COMMUNICATION - ROUTES

```php
<?php

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // SMS
    Route::post('communications/sms/send', [SMSController::class, 'send']);
    Route::post('communications/sms/bulk', [SMSController::class, 'sendBulk']);
    Route::get('communications/sms/history', [SMSController::class, 'history']);
    
    // Emails
    Route::post('communications/emails/send', [EmailController::class, 'send']);
    Route::post('communications/emails/bulk', [EmailController::class, 'sendBulk']);
    
    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    
    // Messages internes
    Route::apiResource('messages', MessageController::class);
    Route::get('messages/inbox', [MessageController::class, 'inbox']);
    Route::get('messages/sent', [MessageController::class, 'sent']);
});
