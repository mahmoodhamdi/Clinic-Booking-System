<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    // Routes a user with must_change_password=true is still allowed to hit, so
    // they can read their profile, change their password, or log out — but
    // nothing else. Match by request path suffix to avoid coupling to route names.
    private const ALLOWED_PATHS = [
        'auth/me',
        'auth/logout',
        'auth/change-password',
        'auth/refresh',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $this->isAllowed($request)) {
            return response()->json([
                'success' => false,
                'message' => __('يجب تغيير كلمة المرور قبل المتابعة.'),
                'error_code' => 'PASSWORD_CHANGE_REQUIRED',
            ], 403);
        }

        return $next($request);
    }

    private function isAllowed(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach (self::ALLOWED_PATHS as $allowed) {
            if (str_ends_with($path, $allowed)) {
                return true;
            }
        }

        return false;
    }
}
