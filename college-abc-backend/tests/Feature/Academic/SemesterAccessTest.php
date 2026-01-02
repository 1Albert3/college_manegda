<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Academic\Entities\Semester;

use Modules\Academic\Entities\AcademicYear;

class SemesterAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\Core\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_create_semester()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $academicYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30'
        ]);

        try {
            $response = $this->actingAs($admin)
                ->postJson('/api/v1/semesters', [
                    'name' => 'Semestre 1',
                    // 'code' removed
                    'number' => 1,
                    'type' => 'semester',
                    'academic_year_id' => $academicYear->id,
                    'start_date' => '2025-09-01',
                    'end_date' => '2026-01-30',
                    'is_current' => true
                ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        $response->assertStatus(201);
    }

    public function test_student_cannot_create_semester()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $academicYear = AcademicYear::create([
            'name' => '2025-2026-S',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30'
        ]);

        $response = $this->actingAs($student)
            ->postJson('/api/v1/semesters', [
                'name' => 'Semestre Hacked',
                'number' => 1,
                'academic_year_id' => $academicYear->id,
                'start_date' => '2025-09-01',
                'end_date' => '2026-01-30',
            ]);

        $response->assertStatus(403);
    }

    public function test_public_cannot_access_semesters()
    {
        $response = $this->getJson('/api/v1/semesters');
        $response->assertStatus(401);
    }
}
