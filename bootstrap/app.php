<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'secretary' => \App\Http\Middleware\SecretaryMiddleware::class,
            'cache.api' => \App\Http\Middleware\CacheApiResponse::class,
        ]);

        // Add cookie authentication, security headers and request ID to all API responses
        $middleware->api(prepend: [
            \App\Http\Middleware\AuthenticateFromCookie::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\AddRequestId::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\BusinessLogicException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                ], $e->getCode());
            }
        });

        $exceptions->render(function (\App\Exceptions\PaymentException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $e->getErrorCode(),
                    'appointment_id' => $e->getAppointmentId(),
                    'amount' => $e->getAmount(),
                ], $e->getCode());
            }
        });
    })->create();
