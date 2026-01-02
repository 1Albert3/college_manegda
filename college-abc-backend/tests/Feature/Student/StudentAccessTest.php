<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Core\Entities\User;
use Modules\Student\Entities\Student;

class StudentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\Core\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_parent_can_view_own_child()
    {
        // 1. Create Parent
        $parentUser = User::factory()->create();
        $parentUser->assignRole('parent'); // Ensure role allows 'view-students' generally (or specific permission logic)

        // 2. Create Student linked to Parent
        // We need to create a Student manually because validation in CreateStudent might be complex
        // Or use a factory if available. Let's try Manual first to control fields.
        // A Student also needs a User account usually
        $studentUser = User::factory()->create(['role_type' => 'student']);
        
        $student = new Student([
            'user_id' => $studentUser->id,
            'matricule' => 'STU-TEST-001',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'date_of_birth' => '2010-01-01',
            'place_of_birth' => 'Ouagadougou',
            'gender' => 'M', 
            'status' => 'active'
        ]);
        $student->save();

        // Attach Parent
        $student->parents()->attach($parentUser->id, ['relationship' => 'father', 'is_primary' => true]);

        // 3. Act
        $response = $this->actingAs($parentUser)->getJson("/api/v1/students/{$student->id}");

        // 4. Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $student->id);
    }

    public function test_parent_cannot_view_other_child()
    {
        // 1. Create Parent
        $otherParent = User::factory()->create();
        $otherParent->assignRole('parent');

        // 2. Create Student NOT linked
        $studentUser = User::factory()->create();
        $student = new Student([
            'user_id' => $studentUser->id,
            'matricule' => 'STU-TEST-002',
            'first_name' => 'Paul',
            'last_name' => 'Durand',
            'date_of_birth' => '2010-01-01',
            'place_of_birth' => 'Bobo',
            'gender' => 'M', 
            'status' => 'active'
        ]);
        $student->save();

        // 3. Act
        $response = $this->actingAs($otherParent)->getJson("/api/v1/students/{$student->id}");

        // 4. Assert
        $response->assertStatus(403);
    }
}
