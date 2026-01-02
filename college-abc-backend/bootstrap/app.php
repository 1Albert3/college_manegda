<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'handle.api.errors' => \App\Http\Middleware\HandleApiErrors::class,
            'optional.auth' => \App\Http\Middleware\OptionalAuth::class,
            'auth.simple' => \App\Http\Middleware\SimpleAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // Return 404 for model not found
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ressource introuvable.',
                    ], 404);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Non authentifié.',
                    ], 401);
                }

                // Return 422 for validation errors
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de validation.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Action non autorisée.',
                    ], 403);
                }

                // Handle other exceptions
                $statusCode = ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
                    ? $e->getStatusCode()
                    : 500;

                // Generic error message for production 500 errors
                $message = ($statusCode == 500 && !config('app.debug'))
                    ? 'Une erreur interne est survenue.'
                    : $e->getMessage();

                $response = [
                    'success' => false,
                    'message' => $message,
                ];

                // Add debug info if in debug mode
                if (config('app.debug')) {
                    $response['debug'] = [
                        'message' => $e->getMessage(),
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => explode("\n", $e->getTraceAsString()),
                    ];
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
