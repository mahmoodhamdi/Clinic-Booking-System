# Phase 4: API & Validation Hardening

## Overview
هذه المرحلة تركز على تحسين الـ Rate Limiting وتقوية الـ Input Validation.

**الأولوية:** عالية
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 1

---

## Pre-requisites Checklist
- [ ] Phase 1 completed
- [ ] All tests passing: `php artisan test`

---

## Milestone 4.1: Add Rate Limiting to Slots Endpoints

### المشكلة
الـ Slots endpoints ليس لها rate limiting مما يعرضها للـ abuse.

### الملف المتأثر
`routes/api.php`

### المهام

#### Task 4.1.1: Configure Rate Limiters
الملف: `app/Providers/AppServiceProvider.php`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // Existing code...

    $this->configureRateLimiting();
}

protected function configureRateLimiting(): void
{
    // API rate limiter for authenticated users
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Stricter rate limiter for public endpoints
    RateLimiter::for('public', function (Request $request) {
        return Limit::perMinute(30)->by($request->ip());
    });

    // Very strict rate limiter for auth endpoints
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });

    // Rate limiter for slots (medium strictness)
    RateLimiter::for('slots', function (Request $request) {
        return Limit::perMinute(20)->by($request->ip());
    });

    // Rate limiter for booking (very strict to prevent spam)
    RateLimiter::for('booking', function (Request $request) {
        return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
    });
}
```

#### Task 4.1.2: Update Routes with Rate Limiters
الملف: `routes/api.php`

```php
// Public slots routes with rate limiting
Route::prefix('slots')->middleware('throttle:slots')->group(function () {
    Route::get('/dates', [SlotController::class, 'dates']);
    Route::get('/next', [SlotController::class, 'next']);
    Route::post('/check', [SlotController::class, 'check']);
    Route::get('/{date}', [SlotController::class, 'slots']);
});

// Patient appointment routes with booking rate limit
Route::middleware(['auth:sanctum'])->prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'index']);
    Route::get('/upcoming', [AppointmentController::class, 'upcoming']);
    Route::post('/', [AppointmentController::class, 'store'])->middleware('throttle:booking');
    Route::post('/check', [AppointmentController::class, 'checkBooking'])->middleware('throttle:slots');
    Route::get('/{appointment}', [AppointmentController::class, 'show']);
    Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
});
```

### Verification
```bash
php artisan test --filter=Slot
php artisan test --filter=Appointment
```

---

## Milestone 4.2: Strengthen Login Validation

### المشكلة
الـ Login validation ضعيف جداً - يقبل أي string.

### الملف المتأثر
`app/Http/Requests/Auth/LoginRequest.php`

### المهام

#### Task 4.2.1: Update LoginRequest Validation
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^01[0125][0-9]{8}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('Phone number is required.'),
            'phone.regex' => __('Please enter a valid Egyptian phone number.'),
            'password.required' => __('Password is required.'),
            'password.min' => __('Password must be at least 6 characters.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', '', $this->phone ?? ''),
        ]);
    }
}
```

### Verification
```bash
php artisan test --filter=Login
```

---

## Milestone 4.3: Implement Global API Rate Limiting

### المشكلة
الـ authenticated routes ليس لها rate limiting.

### المهام

#### Task 4.3.1: Apply Rate Limiting to All API Routes
الملف: `routes/api.php`

```php
// Wrap all authenticated routes with API rate limiter
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Protected auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);
    });

    // Patient routes...
    // Admin routes...
});
```

#### Task 4.3.2: Add Rate Limit Headers Middleware
الملف: `app/Http/Middleware/AddRateLimitHeaders.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddRateLimitHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add remaining attempts to response headers
        if ($request->hasHeader('X-RateLimit-Remaining')) {
            $response->headers->set(
                'X-RateLimit-Remaining',
                $request->header('X-RateLimit-Remaining')
            );
        }

        return $response;
    }
}
```

### Verification
```bash
php artisan test
```

---

## Milestone 4.4: Standardize Error Responses

### المشكلة
الـ error responses ليست موحدة في كل الـ API.

### المهام

#### Task 4.4.1: Create ApiResponse Helper
الملف: `app/Http/Helpers/ApiResponse.php`

```php
<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('Operation successful.'),
            'data' => $data,
        ], $code);
    }

    public static function created(mixed $data = null, string $message = null): JsonResponse
    {
        return self::success($data, $message ?? __('Resource created successfully.'), 201);
    }

    public static function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    public static function notFound(string $message = null): JsonResponse
    {
        return self::error($message ?? __('Resource not found.'), 404);
    }

    public static function unauthorized(string $message = null): JsonResponse
    {
        return self::error($message ?? __('Unauthorized.'), 401);
    }

    public static function forbidden(string $message = null): JsonResponse
    {
        return self::error($message ?? __('Access denied.'), 403);
    }

    public static function validationError(array $errors): JsonResponse
    {
        return self::error(__('Validation failed.'), 422, $errors);
    }

    public static function tooManyRequests(string $message = null): JsonResponse
    {
        return self::error($message ?? __('Too many requests. Please try again later.'), 429);
    }

    public static function serverError(string $message = null): JsonResponse
    {
        return self::error($message ?? __('An unexpected error occurred.'), 500);
    }

    public static function paginated($paginator, string $resourceClass = null): JsonResponse
    {
        $data = $resourceClass
            ? $resourceClass::collection($paginator)
            : $paginator->items();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
```

#### Task 4.4.2: Update Exception Handler
الملف: `app/Exceptions/Handler.php` أو `bootstrap/app.php`

```php
use App\Http\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->expectsJson()) {
            return ApiResponse::unauthorized(__('Please login to continue.'));
        }
    });

    $exceptions->render(function (ValidationException $e, Request $request) {
        if ($request->expectsJson()) {
            return ApiResponse::validationError($e->errors());
        }
    });

    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        if ($request->expectsJson()) {
            return ApiResponse::notFound();
        }
    });

    $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
        if ($request->expectsJson()) {
            return ApiResponse::tooManyRequests();
        }
    });
});
```

#### Task 4.4.3: Update Controllers to Use ApiResponse
مثال في `app/Http/Controllers/Api/AuthController.php`:

```php
use App\Http\Helpers\ApiResponse;

public function login(LoginRequest $request): JsonResponse
{
    $user = User::where('phone', $request->phone)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return ApiResponse::unauthorized(__('Invalid credentials.'));
    }

    if (!$user->is_active) {
        return ApiResponse::forbidden(__('Your account has been deactivated.'));
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return ApiResponse::success([
        'user' => new UserResource($user),
    ], __('Login successful.'))
        ->withCookie($this->createAuthCookie($token))
        ->withCookie($this->createUserCookie($user));
}
```

### Verification
```bash
php artisan test
```

---

## Post-Phase Checklist

### Tests
- [ ] All tests pass: `php artisan test`
- [ ] Rate limiting tests: `php artisan test --filter=RateLimit`
- [ ] Validation tests: `php artisan test --filter=Validation`

### Manual Testing
- [ ] Rate limiting works on slots endpoints
- [ ] Rate limiting works on booking
- [ ] Login validation rejects invalid phone formats
- [ ] Error responses are consistent

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
php artisan test --coverage --min=100 && git add -A && git commit -m "feat(api): implement Phase 4 - API & Validation Hardening

- Add rate limiting to slots and booking endpoints
- Implement global API rate limiting for authenticated routes
- Strengthen login validation with phone format check
- Create ApiResponse helper for consistent responses
- Update exception handler for standardized error responses

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
