<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompleteAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function can_get_available_roles()
    {
        $response = $this->getJson('/api/auth/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'roles' => [
                    '*' => ['value', 'label', 'icon', 'description', 'requires_2fa']
                ]
            ]);
    }

    /** @test */
    public function can_login_with_admin_role()
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
            'role' => 'admin'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'role'],
                'token',
                'message'
            ])
            ->assertJson([
                'user' => ['role' => 'admin']
            ]);
    }

    /** @test */
    public function can_login_with_teacher_role()
    {
        $user = User::factory()->create([
            'email' => 'teacher@test.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'teacher@test.com',
            'password' => 'password',
            'role' => 'teacher'
        ]);

        $response->assertStatus(200)
            ->assertJson(['user' => ['role' => 'teacher']]);
    }

    /** @test */
    public function cannot_login_with_wrong_role()
    {
        $user = User::factory()->create([
            'email' => 'teacher@test.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'teacher@test.com',
            'password' => 'password',
            'role' => 'admin'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    /** @test */
    public function cannot_login_with_inactive_account()
    {
        $user = User::factory()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
            'role' => 'teacher'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function can_get_user_info_when_authenticated()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'role', 'permissions']
            ]);
    }

    /** @test */
    public function can_logout_successfully()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout successful.']);
    }
}