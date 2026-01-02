<?php

namespace Modules\Academic\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Entities\Semester;
use Modules\Academic\Entities\AcademicYear;
use Modules\Core\Entities\User;
use Laravel\Sanctum\Sanctum;

class SemesterApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        
        $this->academicYear = AcademicYear::factory()->create([
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
        ]);
    }

    /** @test */
    public function it_can_list_semesters()
    {
        Semester::factory()->count(3)->create([
            'academic_year_id' => $this->academicYear->id
        ]);

        $response = $this->getJson('/api/v1/semesters');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'type', 'number', 'start_date', 'end_date']
                    ]
                ]);
    }

    /** @test */
    public function it_can_filter_semesters_by_academic_year()
    {
        $year2 = AcademicYear::factory()->create();
        
        Semester::factory()->count(2)->create(['academic_year_id' => $this->academicYear->id]);
        Semester::factory()->count(1)->create(['academic_year_id' => $year2->id]);

        $response = $this->getJson("/api/v1/semesters?academic_year_id={$this->academicYear->id}");

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_create_a_semester()
    {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Trimestre 1',
            'type' => 'trimester',
            'number' => 1,
            'start_date' => '2024-09-01',
            'end_date' => '2024-12-15',
            'is_current' => true,
        ];

        $response = $this->postJson('/api/v1/semesters', $data);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Semestre créé avec succès',
                    'data' => [
                        'name' => 'Trimestre 1',
                        'type' => 'trimester',
                    ]
                ]);

        $this->assertDatabaseHas('semesters', ['name' => 'Trimestre 1']);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_semester()
    {
        $response = $this->postJson('/api/v1/semesters', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'academic_year_id',
                    'type',
                    'number',
                    'start_date',
                    'end_date',
                ]);
    }

    /** @test */
    public function it_validates_end_date_after_start_date()
    {
        $response = $this->postJson('/api/v1/semesters', [
            'academic_year_id' => $this->academicYear->id,
            'type' => 'trimester',
            'number' => 1,
            'start_date' => '2024-12-31',
            'end_date' => '2024-09-01',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_can_generate_trimesters_for_academic_year()
    {
        $response = $this->postJson('/api/v1/semesters/generate', [
            'academic_year_id' => $this->academicYear->id,
            'type' => 'trimester',
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Semestres générés avec succès',
                ])
                ->assertJsonCount(3, 'data');

        $this->assertDatabaseCount('semesters', 3);
    }

    /** @test */
    public function it_can_generate_semesters_for_academic_year()
    {
        $response = $this->postJson('/api/v1/semesters/generate', [
            'academic_year_id' => $this->academicYear->id,
            'type' => 'semester',
        ]);

        $response->assertStatus(201)
                ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('semesters', 2);
    }

    /** @test */
    public function it_can_show_a_semester()
    {
        $semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id
        ]);

        $response = $this->getJson("/api/v1/semesters/{$semester->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $semester->id,
                        'name' => $semester->name,
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_a_semester()
    {
        $semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ancien nom'
        ]);

        $response = $this->putJson("/api/v1/semesters/{$semester->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Semestre mis à jour avec succès',
                ]);

        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'name' => 'Nouveau nom',
        ]);
    }

    /** @test */
    public function it_can_delete_a_semester()
    {
        $semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id
        ]);

        $response = $this->deleteJson("/api/v1/semesters/{$semester->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Semestre supprimé avec succès',
                ]);

        $this->assertSoftDeleted('semesters', ['id' => $semester->id]);
    }

    /** @test */
    public function it_can_set_semester_as_current()
    {
        $semester1 = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'is_current' => true
        ]);
        
        $semester2 = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'is_current' => false
        ]);

        $response = $this->postJson("/api/v1/semesters/{$semester2->id}/set-current");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Semestre défini comme courant avec succès',
                ]);

        $this->assertDatabaseHas('semesters', ['id' => $semester2->id, 'is_current' => true]);
        $this->assertDatabaseHas('semesters', ['id' => $semester1->id, 'is_current' => false]);
    }

    /** @test */
    public function it_can_get_current_semester()
    {
        $currentSemester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'is_current' => true
        ]);

        $response = $this->getJson('/api/v1/semesters/current');

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $currentSemester->id,
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_when_no_current_semester()
    {
        $response = $this->getJson('/api/v1/semesters/current');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Aucun semestre courant défini',
                ]);
    }

    /** @test */
    public function it_can_get_semesters_by_year()
    {
        Semester::factory()->count(3)->create([
            'academic_year_id' => $this->academicYear->id
        ]);

        $response = $this->getJson("/api/v1/semesters/by-year/{$this->academicYear->id}");

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
}
