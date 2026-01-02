<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de vérification des permissions
 * 
 * Vérifie que l'utilisateur possède les permissions requises
 * pour accéder à une ressource
 */
class CheckPermission
{
    /**
     * Handle an incoming request
     *
     * @param string|array $permissions Permission(s) requise(s)
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Superadmin a tous les droits
        if ($user->role === 'direction' && $user->hasPermission('*')) {
            return $next($request);
        }

        // Vérifier les permissions
        $hasPermission = false;

        foreach ($permissions as $permission) {
            // Le format peut être "module.action" ou juste "permission"
            if ($user->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }

            // Vérifier aussi le format avec le module (ex: "inscriptions.create")
            if (str_contains($permission, '.')) {
                [$module, $action] = explode('.', $permission, 2);
                if ($user->hasPermission("{$module}.{$action}")) {
                    $hasPermission = true;
                    break;
                }
            }
        }

        if (!$hasPermission) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission d\'effectuer cette action.',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
