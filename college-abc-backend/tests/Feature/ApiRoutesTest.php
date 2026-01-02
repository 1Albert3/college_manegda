<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    /** @test */
    public function auth_routes_are_accessible()
    {
        $response = $this->getJson('/api/auth/roles');
        $response->assertStatus(200);
        
        $this->assertArrayHasKey('roles', $response->json());
    }

    /** @test */
    public function v1_routes_require_authentication()
    {
        $routes = [
            '/api/v1/students',
            '/api/v1/classes',
            '/api/v1/invoices',
            '/api/v1/payments',
            '/api/v1/fee-types'
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $this->assertEquals(401, $response->getStatusCode(), 
                "Route {$route} should require authentication");
        }
    }

    /** @test */
    public function api_returns_json_responses()
    {
        $response = $this->getJson('/api/auth/roles');
        
        $response->assertHeader('content-type', 'application/json');
        $response->assertJsonStructure([
            'roles' => [
                '*' => ['value', 'label', 'icon', 'description', 'requires_2fa']
            ]
        ]);
    }
}