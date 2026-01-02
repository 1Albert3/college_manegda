<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_login_with_correct_credentials()
    {
        // Créer un utilisateur de test
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Test de connexion
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => 'admin'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'role'],
                'token',
                'message'
            ]);
    }

    /** @test */
    public function returns_422_for_missing_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
            'role' => 'admin'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function returns_422_for_missing_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'role' => 'admin'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}