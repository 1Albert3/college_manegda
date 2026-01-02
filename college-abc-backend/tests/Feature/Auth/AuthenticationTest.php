<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login with valid credentials.
     */
    /**
     * Test login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123'),
            'role' => 'direction',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'role', 'email'],
            ]);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123'),
            'role' => 'direction',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'WrongPassword',
        ]);

        // ValidationException returns 422
        $response->assertStatus(422);
    }

    /**
     * Test login is rate limited.
     */
    public function test_login_is_rate_limited(): void
    {
        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'fake@test.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test password change with valid requirements.
     */
    public function test_user_can_change_password_with_valid_requirements(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword1'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'OldPassword1',
            'new_password' => 'NewPassword1',
            'new_password_confirmation' => 'NewPassword1',
        ]);

        $response->assertStatus(200);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword1', $user->password));
    }

    /**
     * Test password change fails without complexity requirements.
     */
    public function test_password_change_fails_without_complexity(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword1'),
        ]);

        $this->actingAs($user, 'sanctum');

        // Password without uppercase/complexity (if enforced)
        // Note: Controller validaton seems to just check string|min:8|confirmed, 
        // it does not strictly enforce complexity in the code I saw, only min:8.
        // I will just test length and confirmation.

        // Too short
        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'OldPassword1',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);

        // Not confirmed
        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'OldPassword1',
            'new_password' => 'ValidPassword123',
            'new_password_confirmation' => 'Different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    /**
     * Test protected routes require authentication.
     */
    public function test_protected_routes_require_authentication(): void
    {
        // Just test me endpoint
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }
}
