<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    /**
     * Handle an incoming request.
     *
     * If the request has an auth_token cookie but no Authorization header,
     * add the cookie token as a Bearer token for Sanctum authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If already has Authorization header, skip (mobile apps, etc.)
        if ($request->hasHeader('Authorization')) {
            return $next($request);
        }

        // Check for auth_token cookie
        $token = $request->cookie('auth_token');

        if ($token) {
            // Add the token as a Bearer token header
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
