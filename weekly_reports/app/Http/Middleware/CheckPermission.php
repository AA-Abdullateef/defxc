<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Gate a route behind a specific permission rather than a blanket
     * admin check. Usage: ->middleware('permission:manage-leave-requests')
     *
     * Registered as the 'permission' alias in bootstrap/app.php.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! auth()->check() || ! auth()->user()->hasPermission($permission)) {
            abort(403, 'Access denied. You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
