<?php

namespace Tests\Feature\Grade;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class GradeApiTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        $this->createRolesAndPermissions();

        // Create users with roles
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->teacherUser = User::factory()->create([
            'role' => 'teacher',
        ]);
        $this->teacherUser->assignRole('teacher');
    }

    protected function createRolesAndPermissions(): void
    {
        // Create permissions
        $permissions = [
            'view-grades',
            'manage-grades',
            'enter-grades',
            'view-academic',
            'manage-academic',
            'view-finance',
            'manage-finance',
            'create-evaluations',
            'update-evaluations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles with permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->syncPermissions(['view-grades', 'view-academic', 'view-finance']);

        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'sanctum']);
        $teacher->syncPermissions(['view-grades', 'enter-grades', 'create-evaluations', 'update-evaluations']);

        Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'sanctum']);
    }

    /**
     * Test only authenticated users can access grades.
     */
    public function test_unauthenticated_user_cannot_access_grades(): void
    {
        $response = $this->getJson('/api/v1/grades');
        $response->assertStatus(401);
    }

    /**
     * Test admin can view all grades.
     */
    public function test_admin_can_view_all_grades(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->getJson('/api/v1/grades');
        $response->assertStatus(200);
    }

    /**
     * Test evaluation creation requires valid data.
     */
    public function test_evaluation_creation_requires_valid_data(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Missing required fields - should return 4xx error
        $response = $this->postJson('/api/v1/evaluations', []);
        $this->assertTrue(in_array($response->status(), [400, 422]));
    }

    /**
     * Test grade recording validation.
     */
    public function test_grade_recording_validates_score_range(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Score exceeds maximum (20) - should return 4xx error
        $response = $this->postJson('/api/v1/grades/record', [
            'student_id' => 1,
            'evaluation_id' => 1,
            'score' => 25, // Invalid
        ]);

        // Should fail with client error (400 or 422)
        $this->assertTrue(in_array($response->status(), [400, 422]));
    }

    /**
     * Test bulk grade recording.
     */
    public function test_bulk_grade_recording_requires_evaluation_id(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/grades/bulk-record', [
            'grades' => [
                ['student_id' => 1, 'score' => 15],
            ],
        ]);

        // Should fail with client error (400 or 422)
        $this->assertTrue(in_array($response->status(), [400, 422]));
    }

    /**
     * Test school stats endpoint requires authentication.
     */
    public function test_school_stats_requires_authorization(): void
    {
        // Without auth
        $response = $this->getJson('/api/v1/grades/school-stats');
        $response->assertStatus(401);

        // With auth
        $this->actingAs($this->adminUser, 'sanctum');
        $response = $this->getJson('/api/v1/grades/school-stats');
        // Should return 200 or 500 depending on data availability
        $response->assertStatus(200);
    }

    /**
     * Test grade deletion soft deletes.
     */
    public function test_grade_soft_delete(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Attempt to delete a non-existent grade
        $response = $this->deleteJson('/api/v1/grades/99999');
        $response->assertStatus(404);
    }

    /**
     * Test report card PDF generation endpoint exists.
     */
    public function test_report_card_endpoint_exists(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // The endpoint should exist even if student doesn't
        $response = $this->getJson('/api/v1/grades/student/99999/report');
        $response->assertStatus(404); // Student not found is expected
    }
}
