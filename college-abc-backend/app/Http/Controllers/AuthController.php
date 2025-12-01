<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Mock login
        $credentials = $request->validate([
            'identifier' => 'required',
            'password' => 'required',
        ]);

        if ($credentials['identifier'] === 'parent' && $credentials['password'] === 'password') {
             return response()->json([
                'token' => 'mock-token',
                'user' => [
                    'id' => 1,
                    'name' => 'M. & Mme OUEDRAOGO',
                    'role' => 'parent',
                    'email' => 'parent@test.com',
                    'children' => [
                        ['id' => 101, 'firstName' => 'Jean-Pierre', 'lastName' => 'OUEDRAOGO', 'class' => '6ème A']
                    ]
                ]
            ]);
        }
        
        // For testing purposes, accept any login
        return response()->json([
            'token' => 'mock-token',
            'user' => [
                'id' => 1,
                'name' => 'M. & Mme OUEDRAOGO',
                'role' => 'parent',
                'email' => 'parent@test.com',
                'children' => [
                    ['id' => 101, 'firstName' => 'Jean-Pierre', 'lastName' => 'OUEDRAOGO', 'class' => '6ème A']
                ]
            ]
        ]);
    }
}
