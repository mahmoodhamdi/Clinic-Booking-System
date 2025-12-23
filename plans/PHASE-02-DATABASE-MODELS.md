# Phase 2: Database & Model Improvements

## Priority: HIGH
## Estimated Effort: 3-4 days
## Dependencies: Phase 1

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 2: Database & Model Improvements.

Read this file completely, then implement each section in order:
1. Add SoftDeletes to critical models
2. Create migrations for missing indexes
3. Fix relationship redundancy
4. Add missing model casts and scopes
5. Update factories for new changes
6. Add model observers/events

After each migration, run: php artisan test
Create new tests for new functionality.
Maintain 100% test coverage.
```

---

## Checklist

### 1. Add SoftDeletes to Critical Models

**Reason:** Medical and financial data should never be permanently deleted for audit/compliance.

**Models to modify:**
- [ ] `app/Models/Appointment.php`
- [ ] `app/Models/MedicalRecord.php`
- [ ] `app/Models/Prescription.php`
- [ ] `app/Models/Payment.php`

**Migrations to create:**
- [ ] `database/migrations/xxxx_add_soft_deletes_to_appointments_table.php`
- [ ] `database/migrations/xxxx_add_soft_deletes_to_medical_records_table.php`
- [ ] `database/migrations/xxxx_add_soft_deletes_to_prescriptions_table.php`
- [ ] `database/migrations/xxxx_add_soft_deletes_to_payments_table.php`

**Code Example - Model:**
```php
// app/Models/Appointment.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    // ...
}
```

**Code Example - Migration:**
```php
// database/migrations/xxxx_add_soft_deletes_to_appointments_table.php
public function up(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}
```

---

### 2. Add Missing Database Indexes

**Create migration for performance indexes:**

```php
// database/migrations/xxxx_add_performance_indexes.php
public function up(): void
{
    // Appointments table
    Schema::table('appointments', function (Blueprint $table) {
        $table->index(['user_id', 'appointment_date', 'status'], 'idx_appointments_patient_date_status');
        $table->index('cancelled_at');
    });

    // Medical Records table
    Schema::table('medical_records', function (Blueprint $table) {
        $table->index('appointment_id');
        $table->index('created_at');
        $table->index(['patient_id', 'created_at'], 'idx_medical_records_patient_created');
    });

    // Prescriptions table
    Schema::table('prescriptions', function (Blueprint $table) {
        $table->index('valid_until');
        $table->index('is_dispensed');
        $table->index(['medical_record_id', 'is_dispensed'], 'idx_prescriptions_record_dispensed');
    });

    // Payments table
    Schema::table('payments', function (Blueprint $table) {
        $table->index('appointment_id');
        $table->index(['status', 'paid_at'], 'idx_payments_status_paid');
    });

    // Users table
    Schema::table('users', function (Blueprint $table) {
        $table->index('is_active');
        $table->index(['role', 'is_active'], 'idx_users_role_active');
    });

    // Schedules table
    Schema::table('schedules', function (Blueprint $table) {
        $table->index('is_active');
    });
}

public function down(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->dropIndex('idx_appointments_patient_date_status');
        $table->dropIndex(['cancelled_at']);
    });
    // ... drop other indexes
}
```

---

### 3. Fix Relationship Redundancy

**Issue:** `Appointment` and `PatientProfile` have duplicate relationship methods (`patient` and `user` point to same thing).

**Files to modify:**
- [ ] `app/Models/Appointment.php`
- [ ] `app/Models/PatientProfile.php`

**Current (problematic):**
```php
// Appointment.php - DUPLICATE
public function patient(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

**Fixed:**
```php
// Appointment.php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// Keep patient() as alias for backwards compatibility
public function patient(): BelongsTo
{
    return $this->user();
}
```

**Update Controllers/Services:**
Search for `->patient` usage and ensure it still works or update to `->user`.

---

### 4. Add Missing Model Casts

**Files to modify:**
- [ ] `app/Models/MedicalRecord.php`
- [ ] `app/Models/User.php`
- [ ] `app/Models/Attachment.php`

**Code Examples:**

```php
// MedicalRecord.php - Improve vital_signs cast
protected $casts = [
    'follow_up_date' => 'date',
    'vital_signs' => 'array', // Consider creating VitalSigns value object
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];

// Add accessor for typed vital signs
public function getBloodPressureAttribute(): ?string
{
    return $this->vital_signs['blood_pressure'] ?? null;
}

public function getHeartRateAttribute(): ?int
{
    return isset($this->vital_signs['heart_rate'])
        ? (int) $this->vital_signs['heart_rate']
        : null;
}
```

```php
// Attachment.php - Fix file_size type
protected $casts = [
    'file_size' => 'integer',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

---

### 5. Add Missing Model Scopes

**Files to modify:**
- [ ] `app/Models/User.php`
- [ ] `app/Models/Appointment.php`
- [ ] `app/Models/Payment.php`
- [ ] `app/Models/MedicalRecord.php`

**Code Examples:**

```php
// User.php - Add useful scopes
public function scopeVerified(Builder $query): Builder
{
    return $query->whereNotNull('phone_verified_at');
}

public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}

public function scopePatients(Builder $query): Builder
{
    return $query->where('role', UserRole::PATIENT);
}
```

```php
// Appointment.php - Add scopes
public function scopeAwaitingConfirmation(Builder $query): Builder
{
    return $query->where('status', AppointmentStatus::PENDING);
}

public function scopeForDateRange(Builder $query, Carbon $from, Carbon $to): Builder
{
    return $query->whereBetween('appointment_date', [$from, $to]);
}

public function scopeNotCancelled(Builder $query): Builder
{
    return $query->whereNotIn('status', [
        AppointmentStatus::CANCELLED,
    ]);
}
```

```php
// Payment.php - Add scopes
public function scopeUnpaid(Builder $query): Builder
{
    return $query->where('status', PaymentStatus::PENDING);
}

public function scopeForPeriod(Builder $query, Carbon $from, Carbon $to): Builder
{
    return $query->whereBetween('created_at', [$from, $to]);
}
```

```php
// MedicalRecord.php - Add scopes
public function scopeWithDueFollowUps(Builder $query): Builder
{
    return $query->whereNotNull('follow_up_date')
        ->where('follow_up_date', '<=', now());
}

public function scopeRecentFirst(Builder $query): Builder
{
    return $query->orderBy('created_at', 'desc');
}
```

---

### 6. Fix Data Type Issues in Migrations

**Create migration to fix data types:**

```php
// database/migrations/xxxx_fix_data_types.php
public function up(): void
{
    // Fix file_size to handle large files
    Schema::table('attachments', function (Blueprint $table) {
        $table->unsignedBigInteger('file_size')->change();
    });
}
```

**Note:** May need `doctrine/dbal` package for column changes:
```bash
composer require doctrine/dbal
```

---

### 7. Add Model Events/Observers

**Create observers:**
- [ ] `app/Observers/AppointmentObserver.php`
- [ ] `app/Observers/PaymentObserver.php`
- [ ] `app/Observers/UserObserver.php`

**Register in AppServiceProvider:**

```php
// app/Providers/AppServiceProvider.php
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Observers\AppointmentObserver;
use App\Observers\PaymentObserver;
use App\Observers\UserObserver;

public function boot(): void
{
    Appointment::observe(AppointmentObserver::class);
    Payment::observe(PaymentObserver::class);
    User::observe(UserObserver::class);
}
```

**AppointmentObserver Example:**
```php
// app/Observers/AppointmentObserver.php
<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Services\NotificationService;

class AppointmentObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function updated(Appointment $appointment): void
    {
        // Send notification when status changes to cancelled
        if ($appointment->isDirty('status') && $appointment->status === AppointmentStatus::CANCELLED) {
            $this->notificationService->sendAppointmentCancelled($appointment);
        }
    }
}
```

**UserObserver Example:**
```php
// app/Observers/UserObserver.php
<?php

namespace App\Observers;

use App\Models\User;
use App\Enums\UserRole;

class UserObserver
{
    public function created(User $user): void
    {
        // Auto-create patient profile for patient users
        if ($user->role === UserRole::PATIENT && !$user->profile) {
            $user->profile()->create([]);
        }
    }
}
```

---

### 8. Update Factories

**Files to modify:**
- [ ] `database/factories/AppointmentFactory.php`
- [ ] `database/factories/MedicalRecordFactory.php`
- [ ] `database/factories/PaymentFactory.php`

**Add states for soft deletes testing:**

```php
// AppointmentFactory.php
public function deleted(): static
{
    return $this->state(fn (array $attributes) => [
        'deleted_at' => now(),
    ]);
}
```

---

### 9. Remove Sensitive Fields from Mass Assignment

**Files to modify:**
- [ ] `app/Models/User.php`

**Current (problematic):**
```php
protected $fillable = [
    'name', 'email', 'phone', 'password', 'role',
    'date_of_birth', 'gender', 'address', 'avatar',
    'is_active',  // SENSITIVE
    'phone_verified_at',  // SENSITIVE
];
```

**Fixed:**
```php
protected $fillable = [
    'name', 'email', 'phone', 'password',
    'date_of_birth', 'gender', 'address', 'avatar',
];

// Handle role, is_active, phone_verified_at explicitly in controllers
```

---

## Testing Requirements

```bash
# Run migrations
php artisan migrate

# Run all tests
php artisan test

# Create new tests for:
# - Soft delete behavior
# - New scopes
# - Model observers
# - Index performance (optional)
```

**New test examples:**

```php
// tests/Unit/Models/AppointmentTest.php
public function test_appointment_can_be_soft_deleted(): void
{
    $appointment = Appointment::factory()->create();
    $appointment->delete();

    $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    $this->assertNotNull($appointment->fresh()->deleted_at);
}

public function test_soft_deleted_appointments_are_excluded_by_default(): void
{
    $active = Appointment::factory()->create();
    $deleted = Appointment::factory()->create();
    $deleted->delete();

    $appointments = Appointment::all();

    $this->assertCount(1, $appointments);
    $this->assertTrue($appointments->contains($active));
    $this->assertFalse($appointments->contains($deleted));
}

public function test_scope_awaiting_confirmation(): void
{
    Appointment::factory()->pending()->create();
    Appointment::factory()->confirmed()->create();

    $pending = Appointment::awaitingConfirmation()->get();

    $this->assertCount(1, $pending);
}
```

---

## Acceptance Criteria

- [ ] All 4 critical models have SoftDeletes
- [ ] Performance indexes added to all tables
- [ ] No duplicate relationship methods
- [ ] All new scopes tested
- [ ] Observers registered and working
- [ ] Factories updated for new features
- [ ] All tests pass (544+ tests)
- [ ] Migrations are reversible

---

## Rollback Plan

```bash
# Rollback last migration
php artisan migrate:rollback

# If multiple migrations, specify steps
php artisan migrate:rollback --step=5
```

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `app/Models/Appointment.php` | Add SoftDeletes, scopes |
| `app/Models/MedicalRecord.php` | Add SoftDeletes, scopes, casts |
| `app/Models/Prescription.php` | Add SoftDeletes |
| `app/Models/Payment.php` | Add SoftDeletes, scopes |
| `app/Models/User.php` | Remove sensitive fillable, add scopes |
| `app/Observers/AppointmentObserver.php` | Create |
| `app/Observers/PaymentObserver.php` | Create |
| `app/Observers/UserObserver.php` | Create |
| `app/Providers/AppServiceProvider.php` | Register observers |
| `database/migrations/xxxx_*.php` | Multiple new migrations |
| `database/factories/*.php` | Add states |
| `tests/Unit/Models/*.php` | New tests |
