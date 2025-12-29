# Phase 10: Testing & Final Polish

## Overview
هذه المرحلة تركز على كتابة الـ Tests النهائية والمراجعة الأخيرة.

**الأولوية:** عالية - قبل الـ Production
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** جميع المراحل السابقة

---

## Pre-requisites Checklist
- [ ] All previous phases completed
- [ ] Backend running: `composer dev`
- [ ] Frontend running: `cd frontend && npm run dev`

---

## Milestone 10.1: Backend Unit Tests for New Features

### المهام

#### Task 10.1.1: Test Authorization Policies
```php
// tests/Unit/Policies/AppointmentPolicyTest.php
<?php

namespace Tests\Unit\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AppointmentPolicy();
    }

    public function test_admin_can_view_any_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        $this->assertTrue($this->policy->view($admin, $appointment));
    }

    public function test_patient_can_only_view_own_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        $ownAppointment = Appointment::factory()->create(['user_id' => $patient->id]);
        $otherAppointment = Appointment::factory()->create();

        $this->assertTrue($this->policy->view($patient, $ownAppointment));
        $this->assertFalse($this->policy->view($patient, $otherAppointment));
    }

    public function test_patient_can_cancel_own_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->create(['user_id' => $patient->id]);

        $this->assertTrue($this->policy->cancel($patient, $appointment));
    }

    public function test_patient_cannot_cancel_other_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->create();

        $this->assertFalse($this->policy->cancel($patient, $appointment));
    }
}
```

#### Task 10.1.2: Test OTP Brute Force Protection
```php
// tests/Feature/Api/Auth/OtpBruteForceTest.php
<?php

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpBruteForceTest extends TestCase
{
    use RefreshDatabase;

    public function test_locks_after_five_failed_attempts(): void
    {
        $phone = '01012345678';

        // Create OTP record
        DB::table('password_reset_tokens')->insert([
            'phone' => $phone,
            'token' => Hash::make('123456'),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        // Attempt wrong OTP 5 times
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '000000',
            ]);
        }

        // 6th attempt should be locked
        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '000000',
        ]);

        $response->assertStatus(429);
    }

    public function test_correct_otp_works_within_attempts(): void
    {
        $phone = '01012345678';
        $otp = '123456';

        DB::table('password_reset_tokens')->insert([
            'phone' => $phone,
            'token' => Hash::make($otp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $phone,
            'otp' => $otp,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data' => ['reset_token']]);
    }
}
```

#### Task 10.1.3: Test PatientStatisticsService
```php
// tests/Unit/Services/PatientStatisticsServiceTest.php
<?php

namespace Tests\Unit\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Services\PatientStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientStatisticsService();
    }

    public function test_calculates_correct_statistics_for_patient(): void
    {
        $patient = User::factory()->patient()->create();

        Appointment::factory()->count(3)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        Appointment::factory()->count(2)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CANCELLED,
        ]);

        $stats = $this->service->getForPatient($patient);

        $this->assertEquals(5, $stats['total_appointments']);
        $this->assertEquals(3, $stats['completed_appointments']);
        $this->assertEquals(2, $stats['cancelled_appointments']);
    }

    public function test_batch_statistics_returns_correct_data(): void
    {
        $patients = User::factory()->patient()->count(3)->create();

        foreach ($patients as $patient) {
            Appointment::factory()->count(2)->create([
                'user_id' => $patient->id,
                'status' => AppointmentStatus::COMPLETED,
            ]);
        }

        $stats = $this->service->getForPatients($patients);

        $this->assertCount(3, $stats);
        foreach ($patients as $patient) {
            $this->assertEquals(2, $stats[$patient->id]['total_appointments']);
        }
    }
}
```

### Verification
```bash
php artisan test --filter=PolicyTest
php artisan test --filter=OtpBruteForce
php artisan test --filter=PatientStatisticsService
```

---

## Milestone 10.2: Frontend Unit Tests

### المهام

#### Task 10.2.1: Test OTP Input Component
```typescript
// frontend/src/__tests__/components/OtpInput.test.tsx
import { render, screen, fireEvent } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import VerifyOtpPage from "@/app/(auth)/verify-otp/page";

jest.mock("next/navigation", () => ({
  useRouter: () => ({
    push: jest.fn(),
    back: jest.fn(),
  }),
}));

describe("Verify OTP Page", () => {
  beforeEach(() => {
    sessionStorage.setItem("reset_phone", "01012345678");
  });

  afterEach(() => {
    sessionStorage.clear();
  });

  it("renders 6 OTP input fields", () => {
    render(<VerifyOtpPage />);
    const inputs = screen.getAllByRole("textbox");
    expect(inputs).toHaveLength(6);
  });

  it("auto-focuses next input on entry", async () => {
    render(<VerifyOtpPage />);
    const inputs = screen.getAllByRole("textbox");

    await userEvent.type(inputs[0], "1");
    expect(inputs[1]).toHaveFocus();
  });

  it("handles paste of full OTP", async () => {
    render(<VerifyOtpPage />);
    const inputs = screen.getAllByRole("textbox");

    await userEvent.click(inputs[0]);
    await userEvent.paste("123456");

    expect(inputs[0]).toHaveValue("1");
    expect(inputs[1]).toHaveValue("2");
    expect(inputs[5]).toHaveValue("6");
  });
});
```

#### Task 10.2.2: Test Medical Record Detail Page
```typescript
// frontend/src/__tests__/pages/MedicalRecordDetail.test.tsx
import { render, screen, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MedicalRecordDetailPage from "@/app/(patient)/medical-records/[id]/page";
import { patientApi } from "@/lib/api/patient";

jest.mock("@/lib/api/patient");
jest.mock("next/navigation", () => ({
  useParams: () => ({ id: "1" }),
  useRouter: () => ({ back: jest.fn() }),
}));

const mockMedicalRecord = {
  id: 1,
  diagnosis: "Common Cold",
  symptoms: "Fever, cough",
  blood_pressure: "120/80",
  heart_rate: 72,
  temperature: 37.5,
  created_at: "2024-01-15T10:00:00Z",
  prescriptions: [],
  attachments: [],
};

describe("Medical Record Detail Page", () => {
  let queryClient: QueryClient;

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false } },
    });
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });
  });

  it("renders medical record details", async () => {
    render(
      <QueryClientProvider client={queryClient}>
        <MedicalRecordDetailPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("Common Cold")).toBeInTheDocument();
    });

    expect(screen.getByText("Fever, cough")).toBeInTheDocument();
    expect(screen.getByText("120/80")).toBeInTheDocument();
  });

  it("shows loading skeleton initially", () => {
    render(
      <QueryClientProvider client={queryClient}>
        <MedicalRecordDetailPage />
      </QueryClientProvider>
    );

    expect(screen.getByTestId("skeleton")).toBeInTheDocument();
  });
});
```

### Verification
```bash
cd frontend && npm test
```

---

## Milestone 10.3: E2E Tests

### المهام

#### Task 10.3.1: Test Complete User Journey
```typescript
// frontend/e2e/patient-journey.spec.ts
import { test, expect } from "@playwright/test";

test.describe("Patient Journey", () => {
  test.beforeEach(async ({ page }) => {
    // Login as patient
    await page.goto("/login");
    await page.fill('input[name="phone"]', "01012345678");
    await page.fill('input[name="password"]', "password123");
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL("/dashboard");
  });

  test("can view dashboard with real data", async ({ page }) => {
    await expect(page.locator("h1")).toContainText("مرحباً");
    await expect(page.locator('[data-testid="upcoming-appointments"]')).toBeVisible();
  });

  test("can book an appointment", async ({ page }) => {
    await page.click('a[href="/book"]');
    await expect(page).toHaveURL("/book");

    // Select date
    await page.click('[data-testid="available-date"]');

    // Select time slot
    await page.click('[data-testid="time-slot"]');

    // Confirm booking
    await page.click('button:has-text("تأكيد الحجز")');

    await expect(page.locator('[data-testid="success-dialog"]')).toBeVisible();
  });

  test("can view medical records", async ({ page }) => {
    await page.click('a[href="/medical-records"]');
    await expect(page).toHaveURL("/medical-records");

    // Click on first record
    await page.click('[data-testid="medical-record-item"]');

    await expect(page.locator("h1")).toContainText("السجل الطبي");
  });

  test("can update profile", async ({ page }) => {
    await page.click('a[href="/profile"]');

    await page.fill('input[name="name"]', "Updated Name");
    await page.click('button:has-text("حفظ")');

    await expect(page.locator('[data-testid="toast-success"]')).toBeVisible();
  });
});
```

#### Task 10.3.2: Test Admin Journey
```typescript
// frontend/e2e/admin-journey.spec.ts
import { test, expect } from "@playwright/test";

test.describe("Admin Journey", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/login");
    await page.fill('input[name="phone"]', "01000000000");
    await page.fill('input[name="password"]', "admin123");
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL("/admin/dashboard");
  });

  test("dashboard shows real statistics", async ({ page }) => {
    await expect(page.locator('[data-testid="stat-total-patients"]')).toBeVisible();
    await expect(page.locator('[data-testid="stat-today-appointments"]')).toBeVisible();
  });

  test("can manage appointments", async ({ page }) => {
    await page.click('a[href="/admin/appointments"]');

    // Confirm an appointment
    await page.click('[data-testid="appointment-row"]');
    await page.click('button:has-text("تأكيد")');

    await expect(page.locator('[data-testid="toast-success"]')).toBeVisible();
  });

  test("can view patient details", async ({ page }) => {
    await page.click('a[href="/admin/patients"]');
    await page.click('[data-testid="patient-row"]');

    await expect(page.locator('[data-testid="patient-dialog"]')).toBeVisible();
  });
});
```

### Verification
```bash
cd frontend && npm run test:e2e
```

---

## Milestone 10.4: Security Testing

### المهام

#### Task 10.4.1: Test Rate Limiting
```php
// tests/Feature/Api/RateLimitingTest.php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    public function test_login_rate_limiting(): void
    {
        // Attempt login 6 times (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'phone' => '01012345678',
                'password' => 'wrong',
            ]);
        }

        // 6th should be rate limited
        $response->assertStatus(429);
    }

    public function test_slots_rate_limiting(): void
    {
        // Attempt 25 requests (limit is 20)
        for ($i = 0; $i < 25; $i++) {
            $response = $this->getJson('/api/slots/dates');
        }

        $response->assertStatus(429);
    }
}
```

#### Task 10.4.2: Test Authorization
```php
// tests/Feature/Api/AuthorizationTest.php
<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_cannot_access_admin_routes(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)
            ->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(403);
    }

    public function test_patient_cannot_view_other_patient_data(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->create(['user_id' => $otherPatient->id]);

        $response = $this->actingAs($patient)
            ->getJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }
}
```

### Verification
```bash
php artisan test --filter=RateLimiting
php artisan test --filter=Authorization
```

---

## Milestone 10.5: Performance Testing

### المهام

#### Task 10.5.1: Measure API Response Times
```php
// tests/Feature/Api/PerformanceTest.php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_responds_under_200ms(): void
    {
        $admin = User::factory()->admin()->create();

        $start = microtime(true);

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard/stats');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, "Dashboard took {$duration}ms");
    }

    public function test_patient_list_responds_under_300ms(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->patient()->count(100)->create();

        $start = microtime(true);

        $this->actingAs($admin)
            ->getJson('/api/admin/patients');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(300, $duration, "Patient list took {$duration}ms");
    }
}
```

### Verification
```bash
php artisan test --filter=Performance
```

---

## Milestone 10.6: Final Review & Documentation

### المهام

#### Task 10.6.1: Run Full Test Suite
```bash
# Backend
php artisan test --coverage --min=100

# Frontend
cd frontend && npm test -- --coverage

# E2E
cd frontend && npm run test:e2e
```

#### Task 10.6.2: Update Documentation
1. Update README.md with new features
2. Update API documentation
3. Update PROGRESS.md to mark all phases complete
4. Update CLAUDE.md if needed

#### Task 10.6.3: Final Checklist
- [ ] All tests pass
- [ ] No TypeScript errors
- [ ] No ESLint errors
- [ ] Build succeeds
- [ ] All security issues fixed
- [ ] All performance issues fixed
- [ ] All missing pages implemented
- [ ] Auth flow complete
- [ ] API integration complete

### Verification
```bash
# Final verification script
php artisan test --coverage --min=100 && \
cd frontend && npm test && npm run build && \
cd .. && echo "All checks passed!"
```

---

## Post-Phase Checklist

### Tests
- [ ] Backend coverage: 100%
- [ ] Frontend coverage: 80%+
- [ ] E2E tests pass
- [ ] Security tests pass
- [ ] Performance tests pass

### Final Steps
- [ ] Update PROGRESS.md to 100%
- [ ] Create final commit
- [ ] Tag release

---

## Completion Command

```bash
php artisan test --coverage --min=100 && \
cd frontend && npm test && npm run build && cd .. && \
git add -A && git commit -m "feat(testing): implement Phase 10 - Final Testing & Polish

- Add authorization policy tests
- Add OTP brute force protection tests
- Add PatientStatisticsService tests
- Add frontend component tests
- Add E2E tests for patient and admin journeys
- Add security and rate limiting tests
- Add performance tests
- Update documentation

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

## Production Release

بعد اكتمال جميع المراحل:

```bash
git tag -a v1.0.0 -m "Production Release v1.0.0

Features:
- Complete authentication flow with OTP
- Appointment booking and management
- Medical records and prescriptions
- Admin dashboard with real-time statistics
- Multi-language support (Arabic/English)
- Comprehensive security measures
- Optimized performance

🤖 Generated with [Claude Code](https://claude.com/claude-code)"

git push origin v1.0.0
```
