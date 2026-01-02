<?php

namespace Tests\Feature\Student;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCrudTest extends TestCase
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
    }

    /** @test */
    public function can_list_students()
    {
        Student::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'matricule', 'first_name', 'last_name',
                        'date_of_birth', 'gender', 'status'
                    ]
                ],
                'meta' => ['current_page', 'last_page', 'total']
            ]);
    }

    /** @test */
    public function can_create_student()
    {
        $studentData = [
            'first_name' => 'Jean',
            'last_name' => 'KABORE',
            'date_of_birth' => '2010-05-15',
            'place_of_birth' => 'Ouagadougou',
            'gender' => 'M',
            'address' => 'Secteur 12',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/students', $studentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'matricule', 'first_name', 'last_name']
            ]);

        $this->assertDatabaseHas('students', [
            'first_name' => 'Jean',
            'last_name' => 'KABORE',
            'gender' => 'M'
        ]);
    }

    /** @test */
    public function can_show_student()
    {
        $student = Student::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'matricule', 'first_name', 'last_name']
            ]);
    }

    /** @test */
    public function can_update_student()
    {
        $student = Student::factory()->create();

        $updateData = [
            'first_name' => 'Updated Name',
            'address' => 'New Address'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/v1/students/{$student->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'Updated Name',
            'address' => 'New Address'
        ]);
    }

    /** @test */
    public function can_delete_student()
    {
        $student = Student::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    /** @test */
    public function can_get_students_stats()
    {
        Student::factory()->create(['gender' => 'M']);
        Student::factory()->create(['gender' => 'F']);
        Student::factory()->create(['gender' => 'M']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/students-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['total', 'by_gender']
            ])
            ->assertJson([
                'data' => ['total' => 3]
            ]);
    }

    /** @test */
    public function validates_required_fields_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/students', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'date_of_birth', 'gender']);
    }

    /** @test */
    public function validates_gender_enum()
    {
        $studentData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'date_of_birth' => '2010-01-01',
            'gender' => 'X', // Invalid gender
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/students', $studentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }
}