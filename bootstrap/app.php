<?php

use App\Http\Middleware\AdminAccess;
use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Http\Middleware\RequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:     __DIR__ . '/../routes/web.php',
        api:     __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:  '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Attach Request ID to every response for observability
        $middleware->append(RequestId::class);

        // Register named middleware aliases
        $middleware->alias([
            'admin' => AdminAccess::class,
            'onboarded' => EnsureOnboardingCompleted::class,
        ]);

        // Sanctum stateful domains for SPA (if ever needed)
        $middleware->statefulApi();

        // Trust proxies in production (Nginx, load balancers)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Return JSON for API 404s instead of HTML
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested resource was not found.',
                ], 404);
            }
        });

        // Return JSON for API unauthenticated errors
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            // Unauthenticated admin guard requests redirect to admin login —
            // not the default 'login' named route which belongs to the user portal.
            if ($request->is('admin/*')) {
                return redirect()->route('admin.login');
            }
        });

        // Return JSON for API validation errors (belt-and-suspenders for non-FormRequest paths)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // Return JSON for API throttle errors
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please slow down.',
                ], 429);
            }
        });
    })
    ->create();
    