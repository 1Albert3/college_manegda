<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SimpleAuthMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Extrait l'utilisateur à partir du simple-token envoyé par le frontend
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader && preg_match('/simple-token-(.*)/', $authHeader, $matches)) {
            $userId = $matches[1];
            $user = User::find($userId);

            if ($user) {
                // Authentifier l'utilisateur pour la durée de la requête
                Auth::setUser($user);
                $request->setUserResolver(fn() => $user);
            }
        }

        return $next($request);
    }
}
