<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion (Email ou Matricule)
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
            'password' => 'required',
        ]);

        $identifier = $request->identifier;
        $password = $request->password;

        // Recherche utilisateur (Email ou Matricule)
        $user = User::where('email', $identifier)
            ->orWhere('matricule', $identifier)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => ['Votre compte a été désactivé. Veuillez contacter l\'administration.'],
            ]);
        }

        // Création du token Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        // Enregistrement Audit
        $user->recordLogin();

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'role' => $user->role,
            'requires_2fa' => false,
            'must_change_password' => $user->must_change_password ?? false,
            'message' => 'Connexion réussie'
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Utilisateur connecté
     */
    public function me(Request $request)
    {
        return $request->user();
    }
}
