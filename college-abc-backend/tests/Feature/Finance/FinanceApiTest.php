<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = $this->admin->createToken('test')->plainTextToken;
        $this->student = Student::factory()->create();

        // Create tables for testing
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
    public function can_list_invoices()
    {
        // Create test invoice
        DB::table('invoices')->insert([
            'student_id' => $this->student->id,
            'reference' => 'INV-2024-0001',
            'amount' => 50000,
            'description' => 'Frais de scolarité',
            'due_date' => '2024-12-31',
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'reference', 'amount', 'status']
                ],
                'meta'
            ]);
    }

    /** @test */
    public function can_create_invoice()
    {
        $invoiceData = [
            'student_id' => $this->student->id,
            'amount' => 75000,
            'description' => 'Frais d\'inscription',
            'due_date' => '2024-12-31'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/invoices', $invoiceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'reference', 'amount', 'status']
            ]);

        $this->assertDatabaseHas('invoices', [
            'student_id' => $this->student->id,
            'amount' => 75000,
            'description' => 'Frais d\'inscription'
        ]);
    }

    /** @test */
    public function can_show_invoice()
    {
        $invoiceId = DB::table('invoices')->insertGetId([
            'student_id' => $this->student->id,
            'reference' => 'INV-2024-0002',
            'amount' => 60000,
            'description' => 'Test invoice',
            'due_date' => '2024-12-31',
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/invoices/{$invoiceId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'reference', 'amount', 'first_name', 'last_name']
            ]);
    }

    /** @test */
    public function can_list_payments()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/payments');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function can_get_fee_types()
    {
        // Insert test fee type
        DB::table('fee_types')->insert([
            'name' => 'Frais de scolarité',
            'amount' => 50000,
            'is_active' => 1
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/fee-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'amount', 'is_active']
                ]
            ]);
    }

    /** @test */
    public function can_get_finance_stats()
    {
        // Insert test payments
        DB::table('payments')->insert([
            'amount' => 25000,
            'status' => 'completed',
            'payment_date' => '2024-01-15',
            'payment_method' => 'cash',
            'reference' => 'PAY-001'
        ]);

        DB::table('payments')->insert([
            'amount' => 15000,
            'status' => 'pending',
            'payment_date' => '2024-01-16',
            'payment_method' => 'bank',
            'reference' => 'PAY-002'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/finance/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['total_collected', 'total_pending']
            ]);
    }

    /** @test */
    public function validates_invoice_creation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/invoices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'amount', 'description', 'due_date']);
    }

    /** @test */
    public function returns_404_for_nonexistent_invoice()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/invoices/99999');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}