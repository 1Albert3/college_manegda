<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassApiTest extends TestCase
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
    public function can_list_classes()
    {
        Classroom::factory()->count(3)->create(['is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/classes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'level', 'capacity', 'students_count']
                ]
            ]);
    }

    /** @test */
    public function can_show_class()
    {
        $classroom = Classroom::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/classes/{$classroom->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'level', 'students']
            ]);
    }

    /** @test */
    public function can_get_class_students()
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create();
        
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => 'active'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/classes/{$classroom->id}/students");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'first_name', 'last_name', 'matricule']
                ]
            ]);
    }

    /** @test */
    public function returns_404_for_nonexistent_class()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/classes/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function can_get_academic_years()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/academic-years');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function can_get_cycles()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/cycles');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function can_get_levels()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/levels');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }
}