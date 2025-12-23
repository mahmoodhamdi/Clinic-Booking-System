# Phase 1: Critical Security Fixes

## Priority: CRITICAL
## Estimated Effort: 2-3 days
## Dependencies: None

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 1: Critical Security Fixes.

Read this file completely, then implement each item in order:
1. Add rate limiting to authentication endpoints
2. Fix CORS configuration
3. Set token expiration
4. Remove OTP from logs
5. Add security headers middleware
6. Enable session encryption
7. Fix file upload validation
8. Disable debug mode in production config

After each change, run: php artisan test
Ensure all 544 tests still pass.
```

---

## Checklist

### 1. Rate Limiting on Authentication Endpoints
**Files to modify:**
- `routes/api.php`
- `bootstrap/app.php` (if needed)

**Tasks:**
- [ ] Add `throttle:5,1` middleware to `/auth/register` (5 attempts per minute)
- [ ] Add `throttle:5,1` middleware to `/auth/login`
- [ ] Add `throttle:3,1` middleware to `/auth/forgot-password`
- [ ] Add `throttle:5,1` middleware to `/auth/verify-otp`
- [ ] Add `throttle:10,1` middleware to `/slots/*` endpoints
- [ ] Test rate limiting works by hitting endpoints repeatedly

**Code Example:**
```php
// routes/api.php
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:3,1');
});
```

---

### 2. Fix CORS Configuration
**Files to modify:**
- `config/cors.php`
- `.env.example`

**Tasks:**
- [ ] Replace `allowed_origins: ['*']` with specific origins
- [ ] Add `FRONTEND_URL` environment variable
- [ ] Restrict allowed methods
- [ ] Restrict allowed headers
- [ ] Enable credentials support

**Code Example:**
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'Accept-Language',
        'X-Requested-With',
    ],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

```env
# .env.example - Add this line
FRONTEND_URL=http://localhost:3000
```

---

### 3. Set Token Expiration
**Files to modify:**
- `config/sanctum.php`
- `.env.example`

**Tasks:**
- [ ] Set token expiration to 24 hours (1440 minutes)
- [ ] Add environment variable for configurability

**Code Example:**
```php
// config/sanctum.php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 1440), // 24 hours
```

```env
# .env.example
SANCTUM_TOKEN_EXPIRATION=1440
```

---

### 4. Remove OTP from Logs
**Files to modify:**
- `app/Http/Controllers/Api/AuthController.php` (line 196-197)
- `app/Http/Controllers/Web/AuthController.php` (line 118)

**Tasks:**
- [ ] Remove or secure the OTP logging
- [ ] Add placeholder for actual SMS integration

**Code Example:**
```php
// Replace this:
\Log::info("Password reset OTP for {$request->phone}: {$token}");

// With this:
// TODO: Integrate with SMS provider (Twilio, AWS SNS, etc.)
// For now, use a secure notification method
if (app()->environment('local', 'testing')) {
    \Log::debug("OTP request processed for phone ending in " . substr($request->phone, -4));
}
```

---

### 5. Add Security Headers Middleware
**Files to create/modify:**
- `app/Http/Middleware/SecurityHeaders.php` (create)
- `bootstrap/app.php`

**Tasks:**
- [ ] Create SecurityHeaders middleware
- [ ] Register middleware globally
- [ ] Add all essential security headers

**Code Example:**
```php
// app/Http/Middleware/SecurityHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

```php
// bootstrap/app.php - Add to middleware configuration
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    // ... existing middleware
})
```

---

### 6. Enable Session Encryption
**Files to modify:**
- `config/session.php`
- `.env.example`

**Tasks:**
- [ ] Enable session encryption
- [ ] Set secure cookie flag for production
- [ ] Set SameSite to strict

**Code Example:**
```php
// config/session.php
'encrypt' => env('SESSION_ENCRYPT', true),
'secure' => env('SESSION_SECURE_COOKIE', true),
'same_site' => env('SESSION_SAME_SITE', 'strict'),
```

```env
# .env.example
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=false  # Set to true in production
SESSION_SAME_SITE=strict
```

---

### 7. Strengthen File Upload Validation
**Files to modify:**
- `app/Http/Requests/Admin/StoreAttachmentRequest.php`
- `app/Http/Controllers/Api/AuthController.php` (avatar upload)
- `app/Http/Controllers/Api/Admin/ClinicSettingController.php` (logo upload)

**Tasks:**
- [ ] Add server-side extension validation
- [ ] Reduce max file sizes
- [ ] Add MIME type validation
- [ ] Remove SVG from allowed formats (XSS risk)

**Code Example:**
```php
// app/Http/Requests/Admin/StoreAttachmentRequest.php
public function rules(): array
{
    return [
        'file' => [
            'required',
            'file',
            'max:5120', // 5MB max
            'mimes:jpg,jpeg,png,pdf,doc,docx',
            function ($attribute, $value, $fail) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                $extension = strtolower($value->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $fail(__('validation.mimes', ['attribute' => $attribute, 'values' => implode(', ', $allowedExtensions)]));
                }
            },
        ],
    ];
}

// Remove SVG from ClinicSettingController logo validation
'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // Removed svg
```

---

### 8. Disable Debug Mode in Production Config
**Files to modify:**
- `.env.example`

**Tasks:**
- [ ] Set APP_DEBUG=false as default
- [ ] Set LOG_LEVEL=warning for production
- [ ] Add comments for production settings

**Code Example:**
```env
# .env.example

# Application Settings
APP_DEBUG=false  # Set to true only in development
APP_ENV=production

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning  # Use 'debug' only in development
```

---

### 9. Add Token Prefix for Security Scanning
**Files to modify:**
- `.env.example`

**Tasks:**
- [ ] Add Sanctum token prefix

**Code Example:**
```env
# .env.example
SANCTUM_TOKEN_PREFIX=clinic_
```

---

### 10. Strengthen Password Validation
**Files to modify:**
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/ResetPasswordRequest.php`
- `app/Http/Requests/Auth/ChangePasswordRequest.php`

**Tasks:**
- [ ] Add complexity requirements
- [ ] Check against compromised password list

**Code Example:**
```php
use Illuminate\Validation\Rules\Password;

// In rules() method
'password' => [
    'required',
    'confirmed',
    Password::min(8)
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised(),
],
```

---

## Testing Requirements

After completing all changes:

```bash
# Run all backend tests
php artisan test

# Verify rate limiting works
# (Use a tool like curl or Postman)
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"phone":"test","password":"test"}'
done
# Should get 429 Too Many Requests after 5 attempts

# Verify security headers
curl -I http://localhost:8000/api/health
# Should see X-Content-Type-Options, X-Frame-Options, etc.
```

---

## Acceptance Criteria

- [ ] All 544 existing tests pass
- [ ] Rate limiting returns 429 after limit exceeded
- [ ] CORS only allows configured origins
- [ ] Security headers present in all responses
- [ ] No sensitive data in logs
- [ ] Tokens expire after 24 hours
- [ ] File uploads reject invalid extensions

---

## Rollback Plan

If issues occur:
1. Revert rate limiting: Remove `->middleware('throttle:...')` from routes
2. Revert CORS: Set `allowed_origins => ['*']`
3. Revert token expiration: Set `expiration => null`

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `routes/api.php` | Add throttle middleware |
| `config/cors.php` | Restrict origins |
| `config/sanctum.php` | Set expiration |
| `config/session.php` | Enable encryption |
| `app/Http/Middleware/SecurityHeaders.php` | Create new |
| `bootstrap/app.php` | Register middleware |
| `app/Http/Controllers/Api/AuthController.php` | Remove OTP log |
| `app/Http/Controllers/Web/AuthController.php` | Remove OTP log |
| `app/Http/Requests/Admin/StoreAttachmentRequest.php` | Strengthen validation |
| `app/Http/Requests/Auth/RegisterRequest.php` | Password complexity |
| `app/Http/Requests/Auth/ResetPasswordRequest.php` | Password complexity |
| `.env.example` | Add security settings |
