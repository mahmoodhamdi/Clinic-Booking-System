# Phase 1: Critical Security Fixes

## Overview
هذه المرحلة تركز على إصلاح الثغرات الأمنية الحرجة التي يجب إصلاحها قبل الـ Production.

**الأولوية:** حرجة
**الحالة:** لم يبدأ
**التقدم:** 0%

---

## Pre-requisites Checklist
- [ ] Backend server running: `composer dev`
- [ ] Database migrated: `php artisan migrate:fresh --seed`
- [ ] All current tests passing: `php artisan test`

---

## Milestone 1.1: Authorization Checks at Model Level

### المشكلة
Controllers تفحص الـ type فقط لكن لا تتحقق من ملكية البيانات. هذا يعني أن أي admin يمكنه الوصول لأي بيانات.

### الملفات المتأثرة
1. `app/Http/Controllers/Api/Admin/PatientController.php`
2. `app/Http/Controllers/Api/Admin/AppointmentController.php`
3. `app/Http/Controllers/Api/Admin/MedicalRecordController.php`
4. `app/Http/Controllers/Api/Admin/PrescriptionController.php`
5. `app/Http/Controllers/Api/Admin/PaymentController.php`

### المهام

#### Task 1.1.1: Create Policy Classes
أنشئ Policy classes للـ models الرئيسية:

```bash
php artisan make:policy AppointmentPolicy --model=Appointment
php artisan make:policy MedicalRecordPolicy --model=MedicalRecord
php artisan make:policy PrescriptionPolicy --model=Prescription
php artisan make:policy PaymentPolicy --model=Payment
php artisan make:policy PatientProfilePolicy --model=PatientProfile
```

#### Task 1.1.2: Implement AppointmentPolicy
الملف: `app/Policies/AppointmentPolicy.php`

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Staff can view any appointment
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any appointment, patients can view their own
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $appointment->user_id;
    }

    /**
     * Only patients can create appointments for themselves
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::PATIENT;
    }

    /**
     * Staff can update any appointment
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can confirm appointments
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can complete appointments
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can cancel any, patients can cancel their own
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $appointment->user_id;
    }

    /**
     * Staff can mark no-show
     */
    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }
}
```

#### Task 1.1.3: Implement MedicalRecordPolicy
الملف: `app/Policies/MedicalRecordPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, MedicalRecord $record): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $record->patient_id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, MedicalRecord $record): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, MedicalRecord $record): bool
    {
        return $user->isAdmin();
    }
}
```

#### Task 1.1.4: Implement PrescriptionPolicy
الملف: `app/Policies/PrescriptionPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Prescription $prescription): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $prescription->medicalRecord->patient_id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin();
    }

    public function dispense(User $user, Prescription $prescription): bool
    {
        return $user->isStaff();
    }
}
```

#### Task 1.1.5: Implement PaymentPolicy
الملف: `app/Policies/PaymentPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $payment->appointment->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isStaff();
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
```

#### Task 1.1.6: Register Policies in AuthServiceProvider
الملف: `app/Providers/AuthServiceProvider.php`

أضف في الـ `$policies` array:

```php
protected $policies = [
    Appointment::class => AppointmentPolicy::class,
    MedicalRecord::class => MedicalRecordPolicy::class,
    Prescription::class => PrescriptionPolicy::class,
    Payment::class => PaymentPolicy::class,
    PatientProfile::class => PatientProfilePolicy::class,
];
```

#### Task 1.1.7: Update Controllers to Use Policies
عدل الـ Controllers لاستخدام `$this->authorize()`:

**مثال في Admin/AppointmentController.php:**
```php
public function show(Appointment $appointment): JsonResponse
{
    $this->authorize('view', $appointment);

    return response()->json([
        'success' => true,
        'data' => new AppointmentResource($appointment->load(['patient', 'payment', 'medicalRecord'])),
    ]);
}

public function confirm(Appointment $appointment): JsonResponse
{
    $this->authorize('confirm', $appointment);
    // ... rest of the code
}
```

#### Task 1.1.8: Write Tests for Policies
أنشئ tests للتأكد من عمل الـ Policies:

```bash
php artisan make:test Policies/AppointmentPolicyTest
php artisan make:test Policies/MedicalRecordPolicyTest
php artisan make:test Policies/PrescriptionPolicyTest
php artisan make:test Policies/PaymentPolicyTest
```

### Verification
```bash
php artisan test --filter=PolicyTest
```

---

## Milestone 1.2: Patient Data Isolation

### المشكلة
الـ PatientController لا يتحقق بشكل صريح أن المريض يصل فقط لبياناته.

### الملفات المتأثرة
1. `app/Http/Controllers/Api/PatientController.php`
2. `app/Http/Controllers/Api/AppointmentController.php`
3. `app/Http/Controllers/Api/MedicalRecordController.php`
4. `app/Http/Controllers/Api/PrescriptionController.php`

### المهام

#### Task 1.2.1: Add Ownership Scope to Models
الملف: `app/Models/Traits/BelongsToPatient.php`

```php
<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToPatient
{
    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where($this->getPatientForeignKey(), $patientId);
    }

    protected function getPatientForeignKey(): string
    {
        return 'user_id';
    }
}
```

#### Task 1.2.2: Apply Trait to Models
```php
// In Appointment.php
use App\Models\Traits\BelongsToPatient;

class Appointment extends Model
{
    use BelongsToPatient;
}

// In MedicalRecord.php
class MedicalRecord extends Model
{
    use BelongsToPatient;

    protected function getPatientForeignKey(): string
    {
        return 'patient_id';
    }
}
```

#### Task 1.2.3: Update Controllers
الملف: `app/Http/Controllers/Api/MedicalRecordController.php`

```php
public function index(Request $request): JsonResponse
{
    $records = MedicalRecord::forPatient($request->user()->id)
        ->with(['appointment', 'prescriptions'])
        ->latest()
        ->paginate(15);

    return response()->json([
        'success' => true,
        'data' => MedicalRecordResource::collection($records),
        'meta' => [
            'current_page' => $records->currentPage(),
            'last_page' => $records->lastPage(),
            'total' => $records->total(),
        ],
    ]);
}

public function show(MedicalRecord $medicalRecord): JsonResponse
{
    // Verify ownership
    if ($medicalRecord->patient_id !== auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => __('Unauthorized access'),
        ], 403);
    }

    return response()->json([
        'success' => true,
        'data' => new MedicalRecordResource($medicalRecord->load(['appointment', 'prescriptions.items', 'attachments'])),
    ]);
}
```

### Verification
```bash
php artisan test --filter=PatientController
php artisan test --filter=MedicalRecordController
```

---

## Milestone 1.3: Sensitive Data Protection

### المشكلة
ملف `.env.example` يحتوي على كلمات مرور حقيقية.

### الملفات المتأثرة
1. `.env.example`

### المهام

#### Task 1.3.1: Clean .env.example
عدل الملف ليكون:

```env
APP_NAME="Clinic Booking System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

FRONTEND_URL=http://localhost:3000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinic_booking
DB_USERNAME=root
DB_PASSWORD=

# For Docker
DB_ROOT_PASSWORD=

BROADCAST_CONNECTION=log
CACHE_STORE=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database

SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

BCRYPT_ROUNDS=12

SANCTUM_TOKEN_EXPIRATION=240
SANCTUM_TOKEN_PREFIX=clinic_

# SMS Gateway (required for production)
SMS_PROVIDER=
SMS_API_KEY=
SMS_SENDER_ID=

# Mail (optional)
MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"
```

#### Task 1.3.2: Add .env.local to .gitignore
تأكد من وجود هذه الأسطر في `.gitignore`:

```
.env
.env.*
!.env.example
```

### Verification
```bash
git diff .env.example
```

---

## Milestone 1.4: OTP Brute Force Protection

### المشكلة
لا يوجد تتبع لمحاولات الـ OTP الفاشلة.

### الملفات المتأثرة
1. `app/Http/Controllers/Api/AuthController.php`
2. `database/migrations/*_create_password_reset_tokens_table.php`

### المهام

#### Task 1.4.1: Add Attempts Column to Password Reset Tokens
```bash
php artisan make:migration add_attempts_to_password_reset_tokens
```

```php
public function up(): void
{
    Schema::table('password_reset_tokens', function (Blueprint $table) {
        $table->unsignedTinyInteger('attempts')->default(0);
        $table->timestamp('locked_until')->nullable();
    });
}
```

#### Task 1.4.2: Update AuthController::forgotPassword
```php
public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
{
    // Check if already locked
    $existing = DB::table('password_reset_tokens')
        ->where('phone', $request->phone)
        ->first();

    if ($existing && $existing->locked_until && now()->lt($existing->locked_until)) {
        $minutes = now()->diffInMinutes($existing->locked_until);
        return response()->json([
            'success' => false,
            'message' => __('Too many attempts. Try again in :minutes minutes.', ['minutes' => $minutes]),
        ], 429);
    }

    $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['phone' => $request->phone],
        [
            'token' => Hash::make($token),
            'attempts' => 0,
            'locked_until' => null,
            'created_at' => now(),
        ]
    );

    // TODO: Send SMS in production
    Log::info("OTP for {$request->phone}: {$token}");

    return response()->json([
        'success' => true,
        'message' => __('Verification code sent to your phone.'),
    ]);
}
```

#### Task 1.4.3: Update AuthController::verifyOtp
```php
public function verifyOtp(VerifyOtpRequest $request): JsonResponse
{
    $record = DB::table('password_reset_tokens')
        ->where('phone', $request->phone)
        ->first();

    if (!$record) {
        return response()->json([
            'success' => false,
            'message' => __('Invalid verification code.'),
        ], 400);
    }

    // Check if locked
    if ($record->locked_until && now()->lt($record->locked_until)) {
        $minutes = now()->diffInMinutes($record->locked_until);
        return response()->json([
            'success' => false,
            'message' => __('Account locked. Try again in :minutes minutes.', ['minutes' => $minutes]),
        ], 429);
    }

    // Check expiration (15 minutes)
    if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
        DB::table('password_reset_tokens')->where('phone', $request->phone)->delete();
        return response()->json([
            'success' => false,
            'message' => __('Verification code expired. Please request a new one.'),
        ], 400);
    }

    // Verify token
    if (!Hash::check($request->otp, $record->token)) {
        $attempts = $record->attempts + 1;

        if ($attempts >= 5) {
            // Lock for 30 minutes
            DB::table('password_reset_tokens')
                ->where('phone', $request->phone)
                ->update([
                    'attempts' => $attempts,
                    'locked_until' => now()->addMinutes(30),
                ]);

            return response()->json([
                'success' => false,
                'message' => __('Too many failed attempts. Account locked for 30 minutes.'),
            ], 429);
        }

        DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->update(['attempts' => $attempts]);

        return response()->json([
            'success' => false,
            'message' => __('Invalid verification code. :remaining attempts remaining.', [
                'remaining' => 5 - $attempts
            ]),
        ], 400);
    }

    // Success - generate reset token
    $resetToken = Str::random(64);

    DB::table('password_reset_tokens')
        ->where('phone', $request->phone)
        ->update([
            'token' => Hash::make($resetToken),
            'attempts' => 0,
            'locked_until' => null,
        ]);

    return response()->json([
        'success' => true,
        'message' => __('Verification successful.'),
        'data' => [
            'reset_token' => $resetToken,
        ],
    ]);
}
```

### Verification
```bash
php artisan migrate
php artisan test --filter=AuthController
```

---

## Milestone 1.5: Token Expiration Optimization

### المشكلة
Token expiration 24 ساعة طويلة جداً لتطبيق طبي.

### الملفات المتأثرة
1. `config/sanctum.php`
2. `app/Http/Controllers/Api/AuthController.php`

### المهام

#### Task 1.5.1: Update Sanctum Config
الملف: `config/sanctum.php`

```php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 240), // 4 hours instead of 24
```

#### Task 1.5.2: Update .env.example
```env
SANCTUM_TOKEN_EXPIRATION=240
```

#### Task 1.5.3: Implement Token Refresh
الملف: `app/Http/Controllers/Api/AuthController.php`

```php
public function refresh(Request $request): JsonResponse
{
    $user = $request->user();

    // Delete current token
    $user->currentAccessToken()->delete();

    // Create new token
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => __('Token refreshed successfully.'),
    ])->withCookie($this->createAuthCookie($token))
      ->withCookie($this->createUserCookie($user));
}
```

#### Task 1.5.4: Update Cookie Expiration
```php
protected function createAuthCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
{
    $secure = app()->environment('production');

    return cookie(
        'auth_token',
        $token,
        60 * 4,  // 4 hours instead of 24
        '/',
        null,
        $secure,
        true,  // httpOnly
        false,
        'strict'  // Changed from 'lax' to 'strict'
    );
}
```

### Verification
```bash
php artisan test --filter=Auth
```

---

## Post-Phase Checklist

### Tests
- [ ] All existing tests still pass: `php artisan test`
- [ ] New policy tests pass: `php artisan test --filter=Policy`
- [ ] Auth tests pass: `php artisan test --filter=Auth`
- [ ] Coverage maintained: `php artisan test --coverage --min=100`

### Manual Testing
- [ ] Login with correct credentials works
- [ ] Login with wrong credentials fails after 5 attempts
- [ ] OTP verification works
- [ ] OTP lockout works after 5 failed attempts
- [ ] Token expires after 4 hours
- [ ] Token refresh works
- [ ] Admin can access admin routes
- [ ] Patient cannot access admin routes
- [ ] Patient can only see their own data

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes with message: `feat(security): implement Phase 1 - Critical Security Fixes`

---

## Completion Command

بعد الانتهاء من كل المهام، شغل هذا الأمر:

```bash
php artisan test --coverage --min=100 && git add -A && git commit -m "feat(security): implement Phase 1 - Critical Security Fixes

- Add authorization policies for all models
- Implement patient data isolation
- Remove sensitive data from .env.example
- Add OTP brute force protection
- Optimize token expiration to 4 hours
- Add token refresh endpoint

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
