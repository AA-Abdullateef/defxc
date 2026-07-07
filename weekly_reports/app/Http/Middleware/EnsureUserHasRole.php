<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Job applicants are stored in the `users` table (see JobApplicationController)
     * so an accepted applicant can become staff without re-registering. Until
     * they're accepted, though, they have no row in role_user at all.
     *
     * This middleware makes sure "authenticated" and "has staff-portal access"
     * are two different things: a roleless user gets logged out and sent back
     * to login with an explanation, even if they somehow have valid credentials
     * (e.g. via a password-reset flow they weren't meant to use yet).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && ! $user->hasAnyRole()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account does not have staff portal access yet.']);
        }

        return $next($request);
    }
}
