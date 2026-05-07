<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Use existing request ID from header if valid, or generate a new one.
        // Length cap and char-class regex prevent header-injection / log-spam abuse.
        $clientId = $request->header('X-Request-ID');
        $requestId = ($clientId && preg_match('/^[\w\-]{1,64}$/', $clientId))
            ? $clientId
            : Str::uuid()->toString();

        $request->attributes->set('request_id', $requestId);

        // Share into the log context so every log line emitted during this
        // request includes request_id (and optionally user_id) in the JSON
        // formatter's "extra" field. Used by config/logging.php "json" channel.
        $context = ['request_id' => $requestId];
        if ($user = $request->user()) {
            $context['user_id'] = $user->id;
        }
        Log::withContext($context);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
