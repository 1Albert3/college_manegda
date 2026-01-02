<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de vérification des rôles
 * 
 * Vérifie que l'utilisateur possède un des rôles requis
 */
class CheckRole
{
    /**
     * Handle an incoming request
     *
     * @param string|array $roles Rôle(s) autorisé(s)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Vérifier si l'utilisateur a un des rôles
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Accès non autorisé pour ce rôle.',
                'your_role' => $user->role,
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
