<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to add HTTP caching headers to API responses.
 * Only applies to GET requests with successful responses.
 */
class CacheApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  int  $maxAge  Cache duration in seconds
     */
    public function handle(Request $request, Closure $next, int $maxAge = 60): Response
    {
        $response = $next($request);

        // Only cache GET requests with successful responses (2xx)
        if (!$request->isMethod('GET') || $response->getStatusCode() >= 300) {
            return $response;
        }

        // Don't cache if user is authenticated (personalized content)
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-cache');

            return $response;
        }

        // Set caching headers for public endpoints
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}");

        // Generate ETag from content
        $content = $response->getContent();
        if ($content) {
            $etag = md5($content);
            $response->headers->set('ETag', "\"{$etag}\"");

            // Check If-None-Match header for 304 Not Modified
            $requestEtag = $request->header('If-None-Match');
            if ($requestEtag && $requestEtag === "\"{$etag}\"") {
                $response->setStatusCode(304);
                $response->setContent('');
            }
        }

        return $response;
    }
}
