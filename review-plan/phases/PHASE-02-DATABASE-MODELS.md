# Phase 2: Database & Models Optimization

## Overview
هذه المرحلة تركز على إصلاح مشاكل الـ N+1 queries وتحسين أداء قاعدة البيانات.

**الأولوية:** حرجة للأداء
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 1

---

## Pre-requisites Checklist
- [ ] Phase 1 completed
- [ ] All tests passing: `php artisan test`

---

## Milestone 2.1: Fix User Model N+1 Accessors

### المشكلة
الـ User model يحتوي على accessors تعمل queries منفصلة لكل user مما يسبب N+1 problem.

### الملفات المتأثرة
1. `app/Models/User.php` (Lines 180-230)

### المهام

#### Task 2.1.1: Remove N+1 Accessors from User Model
احذف هذه الـ accessors من `app/Models/User.php`:

```php
// DELETE THESE METHODS:
// getTotalAppointmentsAttribute()
// getCompletedAppointmentsCountAttribute()
// getCancelledAppointmentsCountAttribute()
// getNoShowCountAttribute()
// getUpcomingAppointmentsCountAttribute()
// getLastVisitAttribute()
```

#### Task 2.1.2: Create PatientStatisticsService
أنشئ service جديد لحساب الإحصائيات:

```bash
php artisan make:service PatientStatisticsService
```

الملف: `app/Services/PatientStatisticsService.php`

```php
<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientStatisticsService
{
    /**
     * Get statistics for a single patient
     */
    public function getForPatient(User $patient): array
    {
        $stats = $patient->appointments()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status IN (?, ?) AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
                MAX(CASE WHEN status = ? THEN appointment_date ELSE NULL END) as last_visit
            ', [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
            ])
            ->first();

        return [
            'total_appointments' => $stats->total ?? 0,
            'completed_appointments' => $stats->completed ?? 0,
            'cancelled_appointments' => $stats->cancelled ?? 0,
            'no_show_count' => $stats->no_show ?? 0,
            'upcoming_appointments' => $stats->upcoming ?? 0,
            'last_visit' => $stats->last_visit,
        ];
    }

    /**
     * Get statistics for multiple patients in one query
     */
    public function getForPatients(Collection $patients): array
    {
        $patientIds = $patients->pluck('id');

        $stats = DB::table('appointments')
            ->selectRaw('
                user_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status IN (?, ?) AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
                MAX(CASE WHEN status = ? THEN appointment_date ELSE NULL END) as last_visit
            ', [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
            ])
            ->whereIn('user_id', $patientIds)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $result = [];
        foreach ($patients as $patient) {
            $patientStats = $stats->get($patient->id);
            $result[$patient->id] = [
                'total_appointments' => $patientStats->total ?? 0,
                'completed_appointments' => $patientStats->completed ?? 0,
                'cancelled_appointments' => $patientStats->cancelled ?? 0,
                'no_show_count' => $patientStats->no_show ?? 0,
                'upcoming_appointments' => $patientStats->upcoming ?? 0,
                'last_visit' => $patientStats->last_visit ?? null,
            ];
        }

        return $result;
    }
}
```

#### Task 2.1.3: Update PatientResource to Use Service
الملف: `app/Http/Resources/PatientResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    protected ?array $statistics = null;

    public function setStatistics(?array $statistics): self
    {
        $this->statistics = $statistics;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'profile' => $this->whenLoaded('profile', fn() => new PatientProfileResource($this->profile)),
            'statistics' => $this->when($this->statistics !== null, $this->statistics),
        ];
    }
}
```

#### Task 2.1.4: Update Admin PatientController
الملف: `app/Http/Controllers/Api/Admin/PatientController.php`

```php
use App\Services\PatientStatisticsService;

class PatientController extends Controller
{
    public function __construct(
        protected PatientStatisticsService $statisticsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::patients()
            ->with('profile')
            ->select(['id', 'name', 'phone', 'email', 'avatar', 'is_active', 'created_at']);

        // Apply filters...

        $patients = $query->paginate($request->input('per_page', 15));

        // Get all statistics in one query
        $statistics = $this->statisticsService->getForPatients($patients->getCollection());

        // Attach statistics to resources
        $patients->getCollection()->transform(function ($patient) use ($statistics) {
            return (new PatientResource($patient))->setStatistics($statistics[$patient->id] ?? null);
        });

        return response()->json([
            'success' => true,
            'data' => $patients->items(),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ],
        ]);
    }

    public function statistics(User $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $statistics = $this->statisticsService->getForPatient($patient);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
```

### Verification
```bash
php artisan test --filter=PatientController
```

---

## Milestone 2.2: Add Missing Database Indexes

### المشكلة
بعض الـ indexes الهامة غير موجودة.

### المهام

#### Task 2.2.1: Create New Migration for Indexes
```bash
php artisan make:migration add_missing_performance_indexes
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Appointments - individual user_id index for patient lookups
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('user_id', 'appointments_user_id_index');
            $table->index('appointment_date', 'appointments_date_index');
        });

        // Vacations - date indexes for slot availability checks
        Schema::table('vacations', function (Blueprint $table) {
            $table->index('date', 'vacations_date_index');
            $table->index(['date', 'is_active'], 'vacations_date_active_index');
        });

        // Medical records - appointment_id index
        Schema::table('medical_records', function (Blueprint $table) {
            $table->index('appointment_id', 'medical_records_appointment_id_index');
        });

        // Prescriptions - dispensed status index
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->index('is_dispensed', 'prescriptions_dispensed_index');
        });

        // Notifications - read status and user
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_user_id_index');
            $table->dropIndex('appointments_date_index');
        });

        Schema::table('vacations', function (Blueprint $table) {
            $table->dropIndex('vacations_date_index');
            $table->dropIndex('vacations_date_active_index');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('medical_records_appointment_id_index');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropIndex('prescriptions_dispensed_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
        });
    }
};
```

### Verification
```bash
php artisan migrate
php artisan test
```

---

## Milestone 2.3: Implement Proper Eager Loading

### المشكلة
بعض الـ queries لا تستخدم eager loading بشكل صحيح.

### الملفات المتأثرة
1. `app/Http/Controllers/Api/Admin/AppointmentController.php`
2. `app/Http/Controllers/Api/Admin/MedicalRecordController.php`
3. `app/Http/Controllers/Api/Admin/PrescriptionController.php`

### المهام

#### Task 2.3.1: Create Query Scopes for Common Loads
الملف: `app/Models/Appointment.php`

```php
/**
 * Scope for listing with common relationships
 */
public function scopeWithListingRelations(Builder $query): Builder
{
    return $query->with([
        'patient:id,name,phone,avatar',
        'payment:id,appointment_id,status,total',
    ]);
}

/**
 * Scope for detailed view with all relationships
 */
public function scopeWithDetailRelations(Builder $query): Builder
{
    return $query->with([
        'patient:id,name,phone,email,avatar',
        'patient.profile',
        'payment',
        'medicalRecord.prescriptions.items',
        'medicalRecord.attachments',
    ]);
}
```

#### Task 2.3.2: Update Admin AppointmentController
```php
public function index(Request $request): JsonResponse
{
    $query = Appointment::query()
        ->withListingRelations()
        ->select([
            'id', 'user_id', 'appointment_date', 'appointment_time',
            'status', 'notes', 'created_at'
        ]);

    // Apply filters...

    $appointments = $query->paginate($request->input('per_page', 15));

    return response()->json([
        'success' => true,
        'data' => AppointmentResource::collection($appointments),
        // ...
    ]);
}

public function show(Appointment $appointment): JsonResponse
{
    $this->authorize('view', $appointment);

    $appointment->loadMissing([
        'patient.profile',
        'payment',
        'medicalRecord.prescriptions.items',
        'medicalRecord.attachments',
    ]);

    return response()->json([
        'success' => true,
        'data' => new AppointmentResource($appointment),
    ]);
}
```

#### Task 2.3.3: Add Select Columns to Queries
تأكد من استخدام `select()` في كل الـ queries:

```php
// Example in MedicalRecordController
public function index(Request $request): JsonResponse
{
    $query = MedicalRecord::query()
        ->select([
            'id', 'appointment_id', 'patient_id', 'diagnosis',
            'follow_up_date', 'created_at'
        ])
        ->with([
            'patient:id,name,phone',
            'appointment:id,appointment_date',
        ]);

    // ...
}
```

### Verification
```bash
php artisan test
# Check query count in Laravel Debugbar or Telescope
```

---

## Milestone 2.4: Optimize Statistics Queries

### المشكلة
الـ statistics queries تجلب كل البيانات ثم تفلترها في PHP.

### الملفات المتأثرة
1. `app/Services/AppointmentService.php`
2. `app/Services/DashboardService.php`

### المهام

#### Task 2.4.1: Optimize AppointmentService Statistics
الملف: `app/Services/AppointmentService.php`

```php
public function getStatistics(): array
{
    $stats = Appointment::query()
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
            SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN appointment_date > CURDATE() AND status IN (?, ?) THEN 1 ELSE 0 END) as upcoming
        ', [
            AppointmentStatus::PENDING->value,
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::COMPLETED->value,
            AppointmentStatus::CANCELLED->value,
            AppointmentStatus::NO_SHOW->value,
            AppointmentStatus::PENDING->value,
            AppointmentStatus::CONFIRMED->value,
        ])
        ->first();

    return [
        'total' => $stats->total,
        'by_status' => [
            'pending' => $stats->pending,
            'confirmed' => $stats->confirmed,
            'completed' => $stats->completed,
            'cancelled' => $stats->cancelled,
            'no_show' => $stats->no_show,
        ],
        'today' => $stats->today,
        'upcoming' => $stats->upcoming,
    ];
}
```

#### Task 2.4.2: Create Optimized Statistics Queries
الملف: `app/Services/DashboardService.php`

```php
public function getStats(): array
{
    return Cache::remember('dashboard_stats', 600, function () {
        // Single query for all counts
        $counts = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'patient' AND is_active = 1) as total_patients,
                (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()) as today_appointments,
                (SELECT COUNT(*) FROM appointments WHERE appointment_date > CURDATE() AND status IN ('pending', 'confirmed')) as upcoming_appointments,
                (SELECT COALESCE(SUM(total), 0) FROM payments WHERE status = 'paid' AND MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())) as monthly_revenue
        ");

        return [
            'total_patients' => $counts->total_patients,
            'today_appointments' => $counts->today_appointments,
            'upcoming_appointments' => $counts->upcoming_appointments,
            'monthly_revenue' => $counts->monthly_revenue,
        ];
    });
}
```

### Verification
```bash
php artisan test --filter=Dashboard
php artisan test --filter=Statistics
```

---

## Post-Phase Checklist

### Tests
- [ ] All tests pass: `php artisan test`
- [ ] Coverage maintained: `php artisan test --coverage --min=100`

### Performance
- [ ] N+1 queries eliminated (check with Debugbar)
- [ ] Dashboard loads under 200ms
- [ ] Patient list loads under 300ms

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
php artisan test --coverage --min=100 && git add -A && git commit -m "perf(database): implement Phase 2 - Database & Models Optimization

- Create PatientStatisticsService for batch statistics
- Remove N+1 accessor methods from User model
- Add missing database indexes
- Implement proper eager loading scopes
- Optimize statistics queries with raw SQL

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
