# Phase 3: Backend Services Optimization

## Overview
هذه المرحلة تركز على تحسين الـ Services الرئيسية وإصلاح مشاكل الـ queries.

**الأولوية:** عالية
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 2

---

## Pre-requisites Checklist
- [ ] Phase 2 completed
- [ ] All tests passing: `php artisan test`

---

## Milestone 3.1: DashboardService Query Optimization

### المشكلة
الـ DashboardService يحتوي على loops تعمل queries متعددة ويفلتر البيانات في PHP.

### الملف المتأثر
`app/Services/DashboardService.php`

### المهام

#### Task 3.1.1: Optimize getTodayStatistics()
```php
public function getTodayStatistics(): array
{
    $today = now()->toDateString();

    // Single query for all today's stats
    $stats = DB::selectOne("
        SELECT
            COALESCE(SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed,
            COALESCE(SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END), 0) as completed,
            COALESCE(SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled,
            COALESCE(SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END), 0) as no_show,
            COUNT(a.id) as total_appointments,
            COALESCE((SELECT SUM(total) FROM payments WHERE DATE(paid_at) = ? AND status = 'paid'), 0) as revenue,
            COALESCE((SELECT COUNT(*) FROM users WHERE DATE(created_at) = ? AND role = 'patient'), 0) as new_patients
        FROM appointments a
        WHERE a.appointment_date = ?
    ", [$today, $today, $today]);

    return [
        'appointments' => [
            'total' => $stats->total_appointments,
            'pending' => $stats->pending,
            'confirmed' => $stats->confirmed,
            'completed' => $stats->completed,
            'cancelled' => $stats->cancelled,
            'no_show' => $stats->no_show,
        ],
        'revenue' => (float) $stats->revenue,
        'new_patients' => $stats->new_patients,
    ];
}
```

#### Task 3.1.2: Optimize getChartData()
```php
public function getChartData(int $days = 30): array
{
    $startDate = now()->subDays($days - 1)->startOfDay();
    $endDate = now()->endOfDay();

    // Single query for all days
    $appointments = DB::table('appointments')
        ->selectRaw('DATE(appointment_date) as date, COUNT(*) as count')
        ->whereBetween('appointment_date', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date')
        ->toArray();

    $revenue = DB::table('payments')
        ->selectRaw('DATE(paid_at) as date, SUM(total) as total')
        ->where('status', 'paid')
        ->whereBetween('paid_at', [$startDate, $endDate])
        ->groupBy('date')
        ->pluck('total', 'date')
        ->toArray();

    // Fill missing dates with zeros
    $labels = [];
    $appointmentData = [];
    $revenueData = [];

    for ($i = 0; $i < $days; $i++) {
        $date = $startDate->copy()->addDays($i)->toDateString();
        $labels[] = $date;
        $appointmentData[] = $appointments[$date] ?? 0;
        $revenueData[] = (float) ($revenue[$date] ?? 0);
    }

    return [
        'labels' => $labels,
        'datasets' => [
            'appointments' => $appointmentData,
            'revenue' => $revenueData,
        ],
    ];
}
```

#### Task 3.1.3: Optimize getStatusDistribution()
```php
public function getStatusDistribution(): array
{
    $thisMonth = now()->startOfMonth();

    $distribution = Appointment::query()
        ->selectRaw('status, COUNT(*) as count')
        ->where('appointment_date', '>=', $thisMonth)
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

    return [
        'pending' => $distribution[AppointmentStatus::PENDING->value] ?? 0,
        'confirmed' => $distribution[AppointmentStatus::CONFIRMED->value] ?? 0,
        'completed' => $distribution[AppointmentStatus::COMPLETED->value] ?? 0,
        'cancelled' => $distribution[AppointmentStatus::CANCELLED->value] ?? 0,
        'no_show' => $distribution[AppointmentStatus::NO_SHOW->value] ?? 0,
    ];
}
```

#### Task 3.1.4: Optimize getRecentActivity()
```php
public function getRecentActivity(int $limit = 10): array
{
    // Use UNION to get all activities in one query
    $activities = DB::select("
        (
            SELECT
                'appointment' as type,
                a.id,
                a.created_at,
                CONCAT('New appointment booked by ', u.name) as description,
                u.avatar as user_avatar
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT ?
        )
        UNION ALL
        (
            SELECT
                'payment' as type,
                p.id,
                p.paid_at as created_at,
                CONCAT('Payment received: ', p.total, ' EGP') as description,
                u.avatar as user_avatar
            FROM payments p
            JOIN appointments a ON p.appointment_id = a.id
            JOIN users u ON a.user_id = u.id
            WHERE p.status = 'paid'
            ORDER BY p.paid_at DESC
            LIMIT ?
        )
        UNION ALL
        (
            SELECT
                'medical_record' as type,
                mr.id,
                mr.created_at,
                CONCAT('Medical record created for ', u.name) as description,
                u.avatar as user_avatar
            FROM medical_records mr
            JOIN users u ON mr.patient_id = u.id
            ORDER BY mr.created_at DESC
            LIMIT ?
        )
        ORDER BY created_at DESC
        LIMIT ?
    ", [$limit, $limit, $limit, $limit]);

    return collect($activities)->map(fn($item) => [
        'type' => $item->type,
        'id' => $item->id,
        'description' => $item->description,
        'user_avatar' => $item->user_avatar ? Storage::url($item->user_avatar) : null,
        'created_at' => Carbon::parse($item->created_at)->toISOString(),
    ])->toArray();
}
```

### Verification
```bash
php artisan test --filter=Dashboard
```

---

## Milestone 3.2: ReportService Query Optimization

### المشكلة
الـ ReportService يفلتر البيانات في PHP بدلاً من قاعدة البيانات.

### الملف المتأثر
`app/Services/ReportService.php`

### المهام

#### Task 3.2.1: Optimize getAppointmentsReport()
```php
public function getAppointmentsReport(Carbon $from, Carbon $to): array
{
    // Get summary using database aggregation
    $summary = Appointment::query()
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed
        ', [
            AppointmentStatus::COMPLETED->value,
            AppointmentStatus::CANCELLED->value,
            AppointmentStatus::NO_SHOW->value,
            AppointmentStatus::PENDING->value,
            AppointmentStatus::CONFIRMED->value,
        ])
        ->whereBetween('appointment_date', [$from->startOfDay(), $to->endOfDay()])
        ->first();

    // Get appointments with pagination for the list
    $appointments = Appointment::query()
        ->with(['patient:id,name,phone', 'payment:id,appointment_id,total,status'])
        ->select(['id', 'user_id', 'appointment_date', 'appointment_time', 'status', 'created_at'])
        ->whereBetween('appointment_date', [$from->startOfDay(), $to->endOfDay()])
        ->orderBy('appointment_date')
        ->orderBy('appointment_time')
        ->get();

    return [
        'period' => [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ],
        'summary' => [
            'total' => $summary->total,
            'completed' => $summary->completed,
            'cancelled' => $summary->cancelled,
            'no_show' => $summary->no_show,
            'pending' => $summary->pending,
            'confirmed' => $summary->confirmed,
            'completion_rate' => $summary->total > 0
                ? round(($summary->completed / $summary->total) * 100, 2)
                : 0,
        ],
        'appointments' => AppointmentResource::collection($appointments),
    ];
}
```

#### Task 3.2.2: Optimize getRevenueReport()
```php
public function getRevenueReport(Carbon $from, Carbon $to): array
{
    // Get all aggregations in one query
    $stats = DB::selectOne("
        SELECT
            COALESCE(SUM(total), 0) as total_revenue,
            COALESCE(SUM(discount), 0) as total_discounts,
            COALESCE(SUM(amount), 0) as total_base,
            COALESCE(SUM(CASE WHEN method = 'cash' THEN total ELSE 0 END), 0) as cash,
            COALESCE(SUM(CASE WHEN method = 'card' THEN total ELSE 0 END), 0) as card,
            COALESCE(SUM(CASE WHEN method = 'wallet' THEN total ELSE 0 END), 0) as wallet,
            COUNT(*) as total_payments,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_count
        FROM payments
        WHERE status = 'paid'
        AND paid_at BETWEEN ? AND ?
    ", [$from->startOfDay(), $to->endOfDay()]);

    // Daily breakdown
    $dailyRevenue = DB::table('payments')
        ->selectRaw('DATE(paid_at) as date, SUM(total) as revenue, COUNT(*) as count')
        ->where('status', 'paid')
        ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    return [
        'period' => [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ],
        'summary' => [
            'total_revenue' => (float) $stats->total_revenue,
            'total_discounts' => (float) $stats->total_discounts,
            'net_revenue' => (float) ($stats->total_revenue - $stats->total_discounts),
            'total_payments' => $stats->total_payments,
            'average_payment' => $stats->paid_count > 0
                ? round($stats->total_revenue / $stats->paid_count, 2)
                : 0,
        ],
        'by_method' => [
            'cash' => (float) $stats->cash,
            'card' => (float) $stats->card,
            'wallet' => (float) $stats->wallet,
        ],
        'daily_breakdown' => $dailyRevenue->map(fn($day) => [
            'date' => $day->date,
            'revenue' => (float) $day->revenue,
            'count' => $day->count,
        ])->toArray(),
    ];
}
```

#### Task 3.2.3: Optimize getPatientsReport()
```php
public function getPatientsReport(Carbon $from, Carbon $to): array
{
    $stats = DB::selectOne("
        SELECT
            COUNT(*) as total_patients,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_patients,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_patients,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_patients
        FROM users
        WHERE role = 'patient'
    ", [$from->startOfDay(), $to->endOfDay()]);

    // Get top patients by appointments
    $topPatients = User::query()
        ->select(['id', 'name', 'phone', 'avatar'])
        ->where('role', UserRole::PATIENT)
        ->withCount(['appointments' => function ($query) use ($from, $to) {
            $query->whereBetween('appointment_date', [$from, $to]);
        }])
        ->orderByDesc('appointments_count')
        ->limit(10)
        ->get();

    return [
        'period' => [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ],
        'summary' => [
            'total_patients' => $stats->total_patients,
            'new_patients' => $stats->new_patients,
            'active_patients' => $stats->active_patients,
            'inactive_patients' => $stats->inactive_patients,
        ],
        'top_patients' => $topPatients->map(fn($patient) => [
            'id' => $patient->id,
            'name' => $patient->name,
            'phone' => $patient->phone,
            'avatar_url' => $patient->avatar_url,
            'appointments_count' => $patient->appointments_count,
        ])->toArray(),
    ];
}
```

### Verification
```bash
php artisan test --filter=Report
```

---

## Milestone 3.3: SlotGeneratorService Caching Improvements

### المشكلة
الـ SlotGeneratorService يعمل queries في loops ويفقد الـ cache.

### الملف المتأثر
`app/Services/SlotGeneratorService.php`

### المهام

#### Task 3.3.1: Optimize getAvailableDates()
```php
public function getAvailableDates(int $days = 30): array
{
    $cacheKey = "available_dates_{$days}";

    return Cache::remember($cacheKey, 300, function () use ($days) {
        $startDate = now()->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        // Get all vacations in the range
        $vacations = Vacation::query()
            ->where('is_active', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($date) => $date->toDateString())
            ->toArray();

        // Get all schedules
        $schedules = Schedule::where('is_active', true)
            ->get()
            ->keyBy('day_of_week');

        // Get booked slots count per date
        $bookedCounts = Appointment::query()
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->whereIn('status', [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED])
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $availableDates = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);
            $dateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek;

            // Skip if vacation
            if (in_array($dateString, $vacations)) {
                continue;
            }

            // Skip if no schedule for this day
            if (!isset($schedules[$dayOfWeek])) {
                continue;
            }

            $schedule = $schedules[$dayOfWeek];
            $totalSlots = $this->calculateTotalSlots($schedule);
            $bookedCount = $bookedCounts[$dateString] ?? 0;

            if ($bookedCount < $totalSlots) {
                $availableDates[] = [
                    'date' => $dateString,
                    'day_name' => $date->format('l'),
                    'day_name_ar' => $this->getArabicDayName($dayOfWeek),
                    'available_slots' => $totalSlots - $bookedCount,
                    'total_slots' => $totalSlots,
                ];
            }
        }

        return $availableDates;
    });
}

private function calculateTotalSlots(Schedule $schedule): int
{
    $slotDuration = ClinicSetting::get('slot_duration', 30);

    $startTime = Carbon::parse($schedule->start_time);
    $endTime = Carbon::parse($schedule->end_time);

    $totalMinutes = $startTime->diffInMinutes($endTime);

    // Subtract break time if exists
    if ($schedule->break_start && $schedule->break_end) {
        $breakStart = Carbon::parse($schedule->break_start);
        $breakEnd = Carbon::parse($schedule->break_end);
        $totalMinutes -= $breakStart->diffInMinutes($breakEnd);
    }

    return (int) floor($totalMinutes / $slotDuration);
}
```

#### Task 3.3.2: Cache Slot Booking Check
```php
public function isSlotAvailable(string $date, string $time): bool
{
    $cacheKey = "slot_available_{$date}_{$time}";

    return Cache::remember($cacheKey, 60, function () use ($date, $time) {
        // Check vacation
        if (Vacation::where('date', $date)->where('is_active', true)->exists()) {
            return false;
        }

        // Check schedule
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = Schedule::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return false;
        }

        // Check if time is within schedule
        $slotTime = Carbon::parse($time);
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);

        if ($slotTime->lt($startTime) || $slotTime->gte($endTime)) {
            return false;
        }

        // Check if slot is during break
        if ($schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($schedule->break_start);
            $breakEnd = Carbon::parse($schedule->break_end);
            if ($slotTime->gte($breakStart) && $slotTime->lt($breakEnd)) {
                return false;
            }
        }

        // Check if already booked
        return !Appointment::isSlotBooked($date, $time);
    });
}
```

### Verification
```bash
php artisan test --filter=Slot
```

---

## Milestone 3.4: Cache Invalidation Cascade

### المشكلة
الـ cache invalidation لا يشمل كل الـ keys المرتبطة.

### الملفات المتأثرة
1. `app/Observers/AppointmentObserver.php`
2. `app/Observers/ScheduleObserver.php`
3. `app/Observers/VacationObserver.php`

### المهام

#### Task 3.4.1: Create CacheInvalidationService
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    /**
     * Invalidate all dashboard related caches
     */
    public function invalidateDashboard(): void
    {
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_today');
        Cache::forget('dashboard_weekly');
        Cache::forget('dashboard_monthly');
        Cache::forget('dashboard_chart_30');
        Cache::forget('dashboard_chart_7');
        Cache::forget('dashboard_recent_activity');
    }

    /**
     * Invalidate all slot related caches
     */
    public function invalidateSlots(): void
    {
        // Forget available dates caches
        for ($days = 7; $days <= 90; $days += 7) {
            Cache::forget("available_dates_{$days}");
        }

        // Forget individual slot caches (use tags if using Redis)
        // For file/database cache, we'll use a version key
        Cache::increment('slots_version');
    }

    /**
     * Invalidate specific date slots
     */
    public function invalidateDateSlots(string $date): void
    {
        Cache::forget("slots_{$date}");
        $this->invalidateSlots();
    }

    /**
     * Invalidate all caches
     */
    public function invalidateAll(): void
    {
        $this->invalidateDashboard();
        $this->invalidateSlots();
    }
}
```

#### Task 3.4.2: Update AppointmentObserver
```php
<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\CacheInvalidationService;

class AppointmentObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    public function created(Appointment $appointment): void
    {
        $this->invalidateCaches($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        $this->invalidateCaches($appointment);

        // If date changed, invalidate both old and new dates
        if ($appointment->isDirty('appointment_date')) {
            $this->cacheService->invalidateDateSlots($appointment->getOriginal('appointment_date'));
        }
    }

    public function deleted(Appointment $appointment): void
    {
        $this->invalidateCaches($appointment);
    }

    protected function invalidateCaches(Appointment $appointment): void
    {
        $this->cacheService->invalidateDashboard();
        $this->cacheService->invalidateDateSlots($appointment->appointment_date->toDateString());
    }
}
```

#### Task 3.4.3: Update ScheduleObserver
```php
<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Services\CacheInvalidationService;

class ScheduleObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    public function saved(Schedule $schedule): void
    {
        $this->cacheService->invalidateSlots();
    }

    public function deleted(Schedule $schedule): void
    {
        $this->cacheService->invalidateSlots();
    }
}
```

#### Task 3.4.4: Update VacationObserver
```php
<?php

namespace App\Observers;

use App\Models\Vacation;
use App\Services\CacheInvalidationService;

class VacationObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    public function saved(Vacation $vacation): void
    {
        $this->cacheService->invalidateDateSlots($vacation->date->toDateString());
    }

    public function deleted(Vacation $vacation): void
    {
        $this->cacheService->invalidateDateSlots($vacation->date->toDateString());
    }
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
- [ ] Coverage maintained: `php artisan test --coverage --min=100`

### Performance
- [ ] Dashboard API < 200ms
- [ ] Reports API < 500ms
- [ ] Available dates API < 100ms

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
php artisan test --coverage --min=100 && git add -A && git commit -m "perf(services): implement Phase 3 - Backend Services Optimization

- Optimize DashboardService with single queries
- Optimize ReportService with database aggregation
- Improve SlotGeneratorService caching
- Implement CacheInvalidationService for cascade invalidation
- Update all observers for proper cache invalidation

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
