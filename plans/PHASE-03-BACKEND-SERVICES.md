# Phase 3: Backend Services & Transactions

## Priority: HIGH
## Estimated Effort: 2-3 days
## Dependencies: Phase 2

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 3: Backend Services & Transactions.

Read this file completely, then implement each section:
1. Add database transactions to critical operations
2. Fix race conditions in slot booking
3. Add proper error handling and logging
4. Fix ClinicSetting method calls
5. Implement caching for slot generation
6. Add business logic validation

After each change, run: php artisan test
Maintain 100% test coverage.
```

---

## Checklist

### 1. Add Database Transactions to AppointmentService

**File:** `app/Services/AppointmentService.php`

**Issue:** `book()` method (lines 27-42) has no transaction, causing race conditions.

**Current (problematic):**
```php
public function book(User $patient, Carbon $datetime, ?string $notes = null): Appointment
{
    // Validation code...

    // NO TRANSACTION - RACE CONDITION!
    return Appointment::create([...]);
}
```

**Fixed:**
```php
use Illuminate\Support\Facades\DB;

public function book(User $patient, Carbon $datetime, ?string $notes = null): Appointment
{
    return DB::transaction(function () use ($patient, $datetime, $notes) {
        // Lock the slot to prevent double booking
        $existingAppointment = Appointment::where('appointment_date', $datetime->toDateString())
            ->where('appointment_time', $datetime->format('H:i:s'))
            ->whereNotIn('status', [AppointmentStatus::CANCELLED])
            ->lockForUpdate()
            ->first();

        if ($existingAppointment) {
            throw new \InvalidArgumentException(__('هذا الموعد محجوز بالفعل'));
        }

        // Validation code...

        return Appointment::create([
            'user_id' => $patient->id,
            'appointment_date' => $datetime->toDateString(),
            'appointment_time' => $datetime->format('H:i:s'),
            'notes' => $notes,
            'status' => AppointmentStatus::PENDING,
        ]);
    });
}
```

---

### 2. Add Transaction to PaymentService

**File:** `app/Services/PaymentService.php`

**Methods to fix:**
- [ ] `markAsPaid()` (line 67-70)
- [ ] `refund()` (line 72-75)
- [ ] `updatePayment()` (lines 33-65)

**Add validation before status changes:**
```php
public function markAsPaid(Payment $payment, ?string $transactionId = null): Payment
{
    return DB::transaction(function () use ($payment, $transactionId) {
        // Refresh and lock
        $payment = Payment::lockForUpdate()->find($payment->id);

        if ($payment->status !== PaymentStatus::PENDING) {
            throw new \InvalidArgumentException(__('لا يمكن تأكيد هذه الدفعة'));
        }

        $payment->update([
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'transaction_id' => $transactionId,
        ]);

        return $payment->fresh();
    });
}

public function refund(Payment $payment, ?string $reason = null): Payment
{
    return DB::transaction(function () use ($payment, $reason) {
        $payment = Payment::lockForUpdate()->find($payment->id);

        if ($payment->status !== PaymentStatus::PAID) {
            throw new \InvalidArgumentException(__('لا يمكن استرداد هذه الدفعة'));
        }

        $notes = $payment->notes;
        if ($reason) {
            $notes = $notes ? "{$notes}\nRefund reason: {$reason}" : "Refund reason: {$reason}";
        }

        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'notes' => $notes,
        ]);

        return $payment->fresh();
    });
}
```

---

### 3. Add Payment Amount Validation

**File:** `app/Services/PaymentService.php`

**Add validation in `createPayment()`:**
```php
public function createPayment(
    Appointment $appointment,
    float $amount,
    PaymentMethod $method = PaymentMethod::CASH,
    float $discount = 0,
    ?string $notes = null
): Payment {
    // Add validation
    if ($amount <= 0) {
        throw new \InvalidArgumentException(__('المبلغ يجب أن يكون أكبر من صفر'));
    }

    if ($discount < 0) {
        throw new \InvalidArgumentException(__('الخصم لا يمكن أن يكون سالباً'));
    }

    if ($discount > $amount) {
        throw new \InvalidArgumentException(__('الخصم لا يمكن أن يتجاوز المبلغ'));
    }

    // Check for existing payment
    $existingPayment = Payment::where('appointment_id', $appointment->id)
        ->whereNot('status', PaymentStatus::REFUNDED)
        ->first();

    if ($existingPayment) {
        throw new \InvalidArgumentException(__('يوجد دفعة مسجلة لهذا الموعد'));
    }

    return DB::transaction(function () use ($appointment, $amount, $method, $discount, $notes) {
        $total = Payment::calculateTotal($amount, $discount);

        return Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'discount' => $discount,
            'total' => $total,
            'method' => $method,
            'notes' => $notes,
            'status' => PaymentStatus::PENDING,
        ]);
    });
}
```

---

### 4. Fix PrescriptionPdfService - getSetting Method

**File:** `app/Services/PrescriptionPdfService.php` (lines 24-27)

**Issue:** Calls `getSetting()` method that doesn't exist on ClinicSetting model.

**Current (broken):**
```php
'name' => $clinicSetting->getSetting('clinic_name', 'العيادة'),
'address' => $clinicSetting->getSetting('clinic_address', ''),
```

**Fixed:**
```php
'name' => $clinicSetting->clinic_name ?? 'العيادة',
'address' => $clinicSetting->clinic_address ?? '',
'phone' => $clinicSetting->clinic_phone ?? '',
'email' => $clinicSetting->clinic_email ?? '',
```

---

### 5. Add Caching to SlotGeneratorService

**File:** `app/Services/SlotGeneratorService.php`

**Issue:** `getAvailableDates()` (lines 25-44) queries database for each of 30 days.

**Add caching:**
```php
use Illuminate\Support\Facades\Cache;

public function getAvailableDates(int $days = 30): Collection
{
    $cacheKey = "available_dates_{$days}";

    return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($days) {
        $dates = collect();
        $today = now()->startOfDay();

        for ($i = 0; $i <= $days; $i++) {
            $date = $today->copy()->addDays($i);

            if ($this->isDateAvailable($date)) {
                $dates->push([
                    'date' => $date->toDateString(),
                    'day_name' => $date->translatedFormat('l'),
                    'formatted' => $date->translatedFormat('j F Y'),
                    'slots_count' => $this->getSlotsForDate($date)->count(),
                ]);
            }
        }

        return $dates;
    });
}

// Add method to invalidate cache when schedule changes
public function invalidateCache(): void
{
    Cache::forget('available_dates_30');
    Cache::forget('available_dates_7');
    Cache::forget('available_dates_14');
}
```

**Update ScheduleController to invalidate cache:**
```php
// In store, update, destroy, toggle methods:
app(SlotGeneratorService::class)->invalidateCache();
```

---

### 6. Fix Timezone Issues in Slot Filtering

**File:** `app/Services/SlotGeneratorService.php` (lines 87-90)

**Current (problematic):**
```php
if ($date->isToday()) {
    $slots = $slots->filter(function ($time) {
        return Carbon::parse($time)->gt(now());
    });
}
```

**Fixed:**
```php
if ($date->isToday()) {
    $currentTime = now()->format('H:i');
    $slots = $slots->filter(function ($time) use ($currentTime) {
        return $time > $currentTime;
    });
}
```

---

### 7. Add Comprehensive Logging

**Create logging helper trait:**

```php
// app/Traits/LogsActivity.php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    protected function logInfo(string $action, array $context = []): void
    {
        Log::channel('activity')->info($action, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ], $context));
    }

    protected function logWarning(string $action, array $context = []): void
    {
        Log::channel('activity')->warning($action, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ], $context));
    }

    protected function logError(string $action, \Throwable $e, array $context = []): void
    {
        Log::channel('activity')->error($action, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], $context));
    }
}
```

**Add logging to services:**
```php
// AppointmentService.php
use App\Traits\LogsActivity;

class AppointmentService
{
    use LogsActivity;

    public function book(User $patient, Carbon $datetime, ?string $notes = null): Appointment
    {
        return DB::transaction(function () use ($patient, $datetime, $notes) {
            // ... booking logic ...

            $this->logInfo('appointment.booked', [
                'patient_id' => $patient->id,
                'datetime' => $datetime->toIso8601String(),
            ]);

            return $appointment;
        });
    }

    public function cancel(Appointment $appointment, string $cancelledBy, ?string $reason = null): Appointment
    {
        // ... cancel logic ...

        $this->logInfo('appointment.cancelled', [
            'appointment_id' => $appointment->id,
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
        ]);

        return $appointment;
    }
}
```

---

### 8. Extract Hardcoded Values to Config

**File:** `app/Services/AppointmentService.php` (line 61)

**Current:**
```php
if ($noShowCount >= 3) { // Hardcoded
```

**Create config:**
```php
// config/clinic.php
<?php

return [
    'appointments' => [
        'max_no_shows' => env('CLINIC_MAX_NO_SHOWS', 3),
        'advance_booking_days' => env('CLINIC_ADVANCE_BOOKING_DAYS', 30),
        'cancellation_hours' => env('CLINIC_CANCELLATION_HOURS', 24),
    ],
    'slots' => [
        'cache_ttl_minutes' => env('CLINIC_SLOTS_CACHE_TTL', 5),
    ],
];
```

**Update service:**
```php
if ($noShowCount >= config('clinic.appointments.max_no_shows')) {
    throw new \InvalidArgumentException(__('لا يمكنك الحجز بسبب عدم الحضور المتكرر'));
}
```

---

### 9. Fix Report Date Validation

**File:** `app/Services/ReportService.php` (lines 17-69, 71-124)

**Add validation:**
```php
public function getAppointmentsReport(
    ?string $fromDate = null,
    ?string $toDate = null,
    ?string $status = null
): array {
    try {
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : now()->startOfMonth();
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : now()->endOfMonth();
    } catch (\Exception $e) {
        throw new \InvalidArgumentException(__('تاريخ غير صالح'));
    }

    // Validate date range (max 1 year)
    if ($from->diffInDays($to) > 365) {
        throw new \InvalidArgumentException(__('نطاق التاريخ لا يمكن أن يتجاوز سنة واحدة'));
    }

    // ... rest of the method
}
```

---

### 10. Fix DashboardService Parameter Validation

**File:** `app/Services/DashboardService.php` (lines 44-54, 73-98)

**Add validation:**
```php
public function getMonthlyStatistics(?int $month = null, ?int $year = null): array
{
    $month = $month ?? now()->month;
    $year = $year ?? now()->year;

    // Validate month and year
    if ($month < 1 || $month > 12) {
        throw new \InvalidArgumentException(__('الشهر يجب أن يكون بين 1 و 12'));
    }

    $currentYear = now()->year;
    if ($year < 2020 || $year > $currentYear + 1) {
        throw new \InvalidArgumentException(__('السنة غير صالحة'));
    }

    // ... rest of method
}
```

---

### 11. Fix Inconsistent Error Handling

**Standardize exception handling across all services:**

```php
// Create custom exceptions
// app/Exceptions/BusinessLogicException.php
<?php

namespace App\Exceptions;

use Exception;

class BusinessLogicException extends Exception
{
    protected array $context;

    public function __construct(string $message, array $context = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

// app/Exceptions/SlotNotAvailableException.php
class SlotNotAvailableException extends BusinessLogicException {}

// app/Exceptions/PaymentException.php
class PaymentException extends BusinessLogicException {}
```

**Update services to use custom exceptions:**
```php
// Instead of:
throw new \InvalidArgumentException(__('هذا الموعد محجوز بالفعل'));

// Use:
throw new SlotNotAvailableException(__('هذا الموعد محجوز بالفعل'), [
    'date' => $datetime->toDateString(),
    'time' => $datetime->format('H:i'),
]);
```

---

## Testing Requirements

```bash
# Run all tests
php artisan test

# Test transaction rollback
php artisan test --filter=AppointmentServiceTest

# Test caching
php artisan test --filter=SlotGeneratorServiceTest
```

**New tests to write:**

```php
// tests/Unit/Services/AppointmentServiceTest.php
public function test_concurrent_booking_is_prevented(): void
{
    $datetime = now()->addDays(1)->setTime(10, 0);
    $patient1 = User::factory()->patient()->create();
    $patient2 = User::factory()->patient()->create();

    // First booking should succeed
    $appointment = $this->service->book($patient1, $datetime);
    $this->assertNotNull($appointment);

    // Second booking at same time should fail
    $this->expectException(\InvalidArgumentException::class);
    $this->service->book($patient2, $datetime);
}

public function test_booking_creates_appointment_in_transaction(): void
{
    // Test that failed booking doesn't leave partial data
}
```

---

## Acceptance Criteria

- [ ] All critical operations wrapped in transactions
- [ ] Race conditions prevented with row locking
- [ ] Slot caching implemented and working
- [ ] All validation in place
- [ ] Comprehensive logging added
- [ ] Custom exceptions created
- [ ] Config values externalized
- [ ] All tests pass

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `app/Services/AppointmentService.php` | Transactions, logging, config |
| `app/Services/PaymentService.php` | Transactions, validation |
| `app/Services/SlotGeneratorService.php` | Caching, timezone fix |
| `app/Services/PrescriptionPdfService.php` | Fix getSetting |
| `app/Services/ReportService.php` | Date validation |
| `app/Services/DashboardService.php` | Parameter validation |
| `app/Exceptions/BusinessLogicException.php` | Create |
| `app/Exceptions/SlotNotAvailableException.php` | Create |
| `app/Traits/LogsActivity.php` | Create |
| `config/clinic.php` | Create |
| `config/logging.php` | Add activity channel |
