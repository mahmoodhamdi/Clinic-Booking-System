# Phase 4: API & Validation Hardening

## Priority: HIGH
## Estimated Effort: 2-3 days
## Dependencies: Phase 3

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 4: API & Validation Hardening.

Read this file completely, then implement each section:
1. Add proper authorization to Form Requests
2. Fix validation gaps in controllers
3. Add pagination limits
4. Add status parameter validation
5. Fix error message handling
6. Standardize API responses

After each change, run: php artisan test
Maintain 100% test coverage.
```

---

## Checklist

### 1. Add Proper Authorization to Form Requests

**Issue:** All admin form requests return `true` without checks.

**Files to modify:**
- [ ] `app/Http/Requests/Admin/StoreMedicalRecordRequest.php`
- [ ] `app/Http/Requests/Admin/UpdateMedicalRecordRequest.php`
- [ ] `app/Http/Requests/Admin/StoreAttachmentRequest.php`
- [ ] `app/Http/Requests/Admin/StorePrescriptionRequest.php`
- [ ] `app/Http/Requests/Admin/UpdatePrescriptionRequest.php`
- [ ] `app/Http/Requests/Admin/StorePaymentRequest.php`
- [ ] `app/Http/Requests/Admin/UpdatePaymentRequest.php`
- [ ] `app/Http/Requests/Admin/StoreScheduleRequest.php`
- [ ] `app/Http/Requests/Admin/UpdateScheduleRequest.php`
- [ ] `app/Http/Requests/Admin/StoreVacationRequest.php`
- [ ] `app/Http/Requests/Admin/UpdateVacationRequest.php`

**Code Example:**
```php
// app/Http/Requests/Admin/StoreMedicalRecordRequest.php
public function authorize(): bool
{
    return $this->user()?->isStaff() ?? false;
}

// For update requests that modify existing resources:
// app/Http/Requests/Admin/UpdateMedicalRecordRequest.php
public function authorize(): bool
{
    // Only staff can update
    if (!$this->user()?->isStaff()) {
        return false;
    }

    // Optionally: Check if the record exists and belongs to valid appointment
    $record = $this->route('medicalRecord');
    return $record && $record->exists;
}
```

---

### 2. Add Pagination Validation

**Issue:** Many endpoints allow unlimited `per_page` values.

**Files to modify:**
- [ ] `app/Http/Controllers/Api/PatientController.php` (line 110)
- [ ] `app/Http/Controllers/Api/NotificationController.php` (line 21)
- [ ] `app/Http/Controllers/Api/MedicalRecordController.php`
- [ ] `app/Http/Controllers/Api/PrescriptionController.php`

**Create pagination validation trait:**
```php
// app/Http/Traits/ValidatesPagination.php
<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;

trait ValidatesPagination
{
    protected function getPerPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = $request->integer('per_page', $default);
        return min(max($perPage, 1), $max);
    }

    protected function getLimit(Request $request, int $default = 10, int $max = 100): int
    {
        $limit = $request->integer('limit', $default);
        return min(max($limit, 1), $max);
    }
}
```

**Apply to controllers:**
```php
// PatientController.php
use App\Http\Traits\ValidatesPagination;

class PatientController extends Controller
{
    use ValidatesPagination;

    public function history(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        // ... use $perPage in query
    }
}
```

---

### 3. Add Status Parameter Validation

**Files to modify:**
- [ ] `app/Http/Controllers/Api/Admin/VacationController.php` (lines 18-35)
- [ ] `app/Http/Controllers/Api/Admin/PaymentController.php` (lines 26-35)
- [ ] `app/Http/Controllers/Api/Admin/AppointmentController.php`

**Current (problematic):**
```php
switch ($request->status) {
    case 'upcoming':
    case 'active':
    // ...
}
// Falls through silently if status is invalid
```

**Create form request for filtering:**
```php
// app/Http/Requests/Admin/FilterVacationRequest.php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterVacationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['upcoming', 'active', 'past', 'all'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

**Apply to controller:**
```php
public function index(FilterVacationRequest $request): JsonResponse
{
    $query = Vacation::query();

    $status = $request->validated('status', 'all');
    // ... apply filtering
}
```

---

### 4. Add Dashboard Parameter Validation

**Files to modify:**
- [ ] `app/Http/Controllers/Api/Admin/DashboardController.php`

**Create form request:**
```php
// app/Http/Requests/Admin/DashboardMonthlyRequest.php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DashboardMonthlyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
        ];
    }
}
```

---

### 5. Add Report Date Validation

**Files to modify:**
- [ ] `app/Http/Controllers/Api/Admin/ReportController.php`

**Create form request:**
```php
// app/Http/Requests/Admin/ReportFilterRequest.php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date', 'before_or_equal:to_date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.before_or_equal' => __('تاريخ البداية يجب أن يكون قبل تاريخ النهاية'),
            'to_date.after_or_equal' => __('تاريخ النهاية يجب أن يكون بعد تاريخ البداية'),
        ];
    }
}
```

**Apply to controller:**
```php
public function appointments(ReportFilterRequest $request): JsonResponse
{
    $data = $this->reportService->getAppointmentsReport(
        $request->validated('from_date'),
        $request->validated('to_date'),
        $request->validated('status')
    );
    return response()->json(['success' => true, 'data' => $data]);
}
```

---

### 6. Add Admin Notes Validation

**File:** `app/Http/Controllers/Api/Admin/AppointmentController.php` (lines 128-146)

**Create form request:**
```php
// app/Http/Requests/Admin/CompleteAppointmentRequest.php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
```

---

### 7. Fix Error Message Exposure

**Issue:** Exception messages exposed directly to clients.

**Files to modify:**
- [ ] `app/Http/Controllers/Api/AppointmentController.php` (lines 64-68)
- [ ] `app/Http/Controllers/Api/Admin/AppointmentController.php` (lines 120-124)

**Current (problematic):**
```php
} catch (\InvalidArgumentException $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),  // Exposes raw exception
    ], 422);
}
```

**Fixed - Create exception handler:**
```php
// app/Exceptions/Handler.php (or bootstrap/app.php)
use App\Exceptions\BusinessLogicException;
use Illuminate\Validation\ValidationException;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (BusinessLogicException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->getContext(),
        ], $e->getCode());
    });

    $exceptions->render(function (\InvalidArgumentException $e) {
        // Log the actual error for debugging
        \Log::warning('Business logic error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => __('حدث خطأ في العملية'),
        ], 422);
    });
})
```

---

### 8. Add Payment Amount Upper Limit

**File:** `app/Http/Requests/Admin/StorePaymentRequest.php`

**Add max constraint:**
```php
public function rules(): array
{
    return [
        'appointment_id' => [
            'required',
            'exists:appointments,id',
            function ($attribute, $value, $fail) {
                $appointment = \App\Models\Appointment::find($value);
                if (!$appointment) {
                    $fail(__('الموعد غير موجود'));
                    return;
                }
                if ($appointment->status === \App\Enums\AppointmentStatus::CANCELLED) {
                    $fail(__('لا يمكن إنشاء دفعة لموعد ملغي'));
                }
            },
        ],
        'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
        'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount'],
        'method' => ['required', Rule::enum(PaymentMethod::class)],
        'notes' => ['nullable', 'string', 'max:1000'],
        'mark_as_paid' => ['nullable', 'boolean'],
        'transaction_id' => [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9\-_]+$/', // Only alphanumeric, dash, underscore
        ],
    ];
}
```

---

### 9. Standardize API Response Format

**Create response helper:**
```php
// app/Http/Traits/ApiResponses.php
<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function success($data = null, string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('تمت العملية بنجاح'),
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function created($data = null, string $message = null): JsonResponse
    {
        return $this->success($data, $message ?? __('تم الإنشاء بنجاح'), 201);
    }

    protected function deleted(string $message = null): JsonResponse
    {
        return $this->success(null, $message ?? __('تم الحذف بنجاح'));
    }

    protected function notFound(string $message = null): JsonResponse
    {
        return $this->error($message ?? __('العنصر غير موجود'), null, 404);
    }

    protected function unauthorized(string $message = null): JsonResponse
    {
        return $this->error($message ?? __('غير مصرح'), null, 401);
    }

    protected function forbidden(string $message = null): JsonResponse
    {
        return $this->error($message ?? __('غير مسموح'), null, 403);
    }

    protected function validationError($errors): JsonResponse
    {
        return $this->error(__('بيانات غير صالحة'), $errors, 422);
    }
}
```

**Apply to controllers:**
```php
use App\Http\Traits\ApiResponses;

class AppointmentController extends Controller
{
    use ApiResponses;

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->book(...);
            return $this->created(
                new AppointmentResource($appointment),
                __('تم حجز الموعد بنجاح')
            );
        } catch (SlotNotAvailableException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
```

---

### 10. Add Request ID for Debugging

**Create middleware:**
```php
// app/Http/Middleware/AddRequestId.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();

        // Add to request for logging
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // Add to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
```

---

## Testing Requirements

```bash
# Run all tests
php artisan test

# Test validation
php artisan test --filter=RequestValidationTest
```

**New tests to write:**

```php
// tests/Feature/Api/Admin/ReportControllerTest.php
public function test_report_validates_date_range(): void
{
    $this->actingAs($this->admin);

    $response = $this->getJson('/api/admin/reports/appointments?from_date=2025-12-31&to_date=2025-01-01');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['from_date']);
}

public function test_report_validates_status_enum(): void
{
    $this->actingAs($this->admin);

    $response = $this->getJson('/api/admin/reports/appointments?status=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
}

// tests/Feature/Api/PaginationTest.php
public function test_pagination_respects_max_limit(): void
{
    $this->actingAs($this->patient);

    $response = $this->getJson('/api/patient/history?per_page=1000');

    $response->assertStatus(200);
    $this->assertLessThanOrEqual(100, $response->json('data.per_page'));
}
```

---

## Acceptance Criteria

- [ ] All form requests have proper authorization
- [ ] Pagination limited to 100 max
- [ ] Status parameters validated with enums
- [ ] Date ranges validated
- [ ] Error messages don't expose internals
- [ ] API responses follow standard format
- [ ] Request IDs in all responses
- [ ] All tests pass

---

## Files Modified/Created Summary

| File | Changes |
|------|---------|
| `app/Http/Requests/Admin/*.php` | Add authorize() |
| `app/Http/Requests/Admin/FilterVacationRequest.php` | Create |
| `app/Http/Requests/Admin/ReportFilterRequest.php` | Create |
| `app/Http/Requests/Admin/DashboardMonthlyRequest.php` | Create |
| `app/Http/Requests/Admin/CompleteAppointmentRequest.php` | Create |
| `app/Http/Traits/ValidatesPagination.php` | Create |
| `app/Http/Traits/ApiResponses.php` | Create |
| `app/Http/Middleware/AddRequestId.php` | Create |
| `app/Exceptions/Handler.php` | Update exception rendering |
| `bootstrap/app.php` | Register middleware |
