<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests d'intégration Backend ↔ Frontend
 * Vérifie que les contrats API sont respectés
 */
class BackendFrontendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        // Create necessary tables for testing
        $this->createTestTables();
    }

    private function createTestTables()
    {
        DB::statement('CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            reference VARCHAR(255),
            amount DECIMAL(10,2),
            description TEXT,
            due_date DATE,
            status VARCHAR(50) DEFAULT "pending",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id INTEGER,
            amount DECIMAL(10,2),
            payment_date DATE,
            payment_method VARCHAR(100),
            reference VARCHAR(255),
            status VARCHAR(50) DEFAULT "pending",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS fee_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255),
            amount DECIMAL(10,2),
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /** @test */
    public function frontend_auth_flow_matches_backend_responses()
    {
        // Test 1: Get roles (frontend expects specific structure)
        $response = $this->getJson('/api/auth/roles');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'roles' => [
                    '*' => [
                        'value',      // Frontend expects 'value' field
                        'label',      // Frontend expects 'label' field
                        'icon',       // Frontend expects 'icon' field
                        'description',// Frontend expects 'description' field
                        'requires_2fa'// Frontend expects 'requires_2fa' field
                    ]
                ]
            ]);

        // Test 2: Login response structure
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'admin'
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'first_name',
                    'last_name',
                    'full_name',
                    'role',
                    'profile_photo',
                    'children'  // Frontend expects children array for parents
                ],
                'token',
                'requires_2fa',
                'message'
            ]);

        // Verify role is normalized to English
        $loginResponse->assertJson([
            'user' => ['role' => 'admin']
        ]);
    }

    /** @test */
    public function all_v1_routes_are_accessible()
    {
        $routes = [
            'GET /api/v1/students',
            'GET /api/v1/classes',
            'GET /api/v1/invoices',
            'GET /api/v1/payments',
            'GET /api/v1/fee-types',
            'GET /api/v1/academic-years',
            'GET /api/v1/cycles',
            'GET /api/v1/levels',
            'GET /api/v1/auth/me'
        ];

        foreach ($routes as $route) {
            [$method, $url] = explode(' ', $route);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->json($method, $url);

            // Should not return 404 (route exists)
            $this->assertNotEquals(404, $response->getStatusCode(), 
                "Route {$route} returned 404 - route may not exist");
        }
    }
}