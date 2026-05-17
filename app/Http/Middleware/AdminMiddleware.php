<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow both admin and secretary (staff members)
        if (! $request->user() || ! $request->user()->isStaff()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.auth.forbidden_access'),
                ], 403);
            }

            abort(403, __('messages.auth.forbidden_access'));
        }

        return $next($request);
    }
}
