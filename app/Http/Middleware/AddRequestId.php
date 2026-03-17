<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use existing request ID from header if valid, or generate a new one
        $clientId = $request->header('X-Request-ID');
        $requestId = ($clientId && preg_match('/^[\w\-]{1,64}$/', $clientId))
            ? $clientId
            : Str::uuid()->toString();

        // Add to request attributes for logging
        $request->attributes->set('request_id', $requestId);

        // Process the request
        $response = $next($request);

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
