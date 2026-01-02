<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OptionalAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Permettre l'accès sans authentification
        return $next($request);
    }
}