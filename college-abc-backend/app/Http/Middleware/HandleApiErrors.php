<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class HandleApiErrors
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié. Veuillez vous connecter.',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
                'error_code' => 'UNAUTHORIZED'
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur.',
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }
}