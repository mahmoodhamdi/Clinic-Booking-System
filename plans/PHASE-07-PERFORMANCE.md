# Phase 7: Performance Optimization

## Priority: MEDIUM
## Estimated Effort: 2-3 days
## Dependencies: Phase 3, Phase 6

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 7: Performance Optimization.

Read this file completely, then implement each section:
1. Fix N+1 query problems with eager loading
2. Implement query caching
3. Add database query optimization
4. Optimize frontend rendering
5. Add API response caching
6. Implement pagination optimization

After each change, run tests and verify performance improvements.
Maintain 100% backend test coverage.
```

---

## Checklist

### 1. Fix N+1 Query Problems

**Issue:** Multiple controllers have potential N+1 query issues.

**Files to audit and fix:**
- [ ] `app/Http/Controllers/Api/PatientController.php`
- [ ] `app/Http/Controllers/Api/Admin/PatientController.php`
- [ ] `app/Http/Controllers/Api/Admin/AppointmentController.php`
- [ ] `app/Http/Controllers/Api/Admin/MedicalRecordController.php`

**Example fix - PatientController:**
```php
// Before (N+1)
public function dashboard(Request $request): JsonResponse
{
    $user = $request->user();
    $user->load('profile');  // 1 query

    $upcomingAppointments = $user->appointments()
        ->upcoming()
        ->with('patient')  // This is the user themselves - unnecessary
        ->limit(5)
        ->get();  // Could trigger N+1 if accessing relations

    // ...
}

// After (optimized)
public function dashboard(Request $request): JsonResponse
{
    $user = $request->user()->load('profile');

    $upcomingAppointments = Appointment::where('user_id', $user->id)
        ->upcoming()
        ->with(['medicalRecords.prescriptions'])
        ->limit(5)
        ->get();

    // ...
}
```

**Add eager loading helper trait:**
```php
// app/Http/Traits/EagerLoadsRelations.php
<?php

namespace App\Http\Traits;

trait EagerLoadsRelations
{
    protected function getAppointmentRelations(): array
    {
        return ['user', 'medicalRecords.prescriptions', 'payments'];
    }

    protected function getMedicalRecordRelations(): array
    {
        return ['appointment.user', 'prescriptions.items', 'attachments'];
    }

    protected function getPatientRelations(): array
    {
        return ['profile', 'appointments' => fn($q) => $q->latest()->limit(10)];
    }
}
```

---

### 2. Implement Query Caching

**Add caching to frequently accessed data:**

```php
// app/Services/SlotGeneratorService.php (already partially done in Phase 3)
// Enhance with tag-based cache invalidation

use Illuminate\Support\Facades\Cache;

public function getAvailableDates(int $days = 30): Collection
{
    $cacheKey = "available_dates_{$days}";

    return Cache::tags(['slots', 'schedules'])->remember(
        $cacheKey,
        now()->addMinutes(5),
        fn() => $this->generateAvailableDates($days)
    );
}

public function invalidateCache(): void
{
    Cache::tags(['slots'])->flush();
}
```

**Add caching to ClinicSetting:**
```php
// app/Models/ClinicSetting.php
public static function getInstance(): self
{
    return Cache::remember('clinic_settings', now()->addHours(1), function () {
        return static::first() ?? static::create([
            'clinic_name' => config('app.name'),
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);
    });
}

// Clear cache when settings are updated
protected static function booted()
{
    static::updated(function () {
        Cache::forget('clinic_settings');
    });
}
```

**Add caching to DashboardService:**
```php
// app/Services/DashboardService.php
public function getStats(): array
{
    return Cache::remember('dashboard_stats', now()->addMinutes(5), function () {
        return [
            'totalPatients' => User::patients()->count(),
            'todayAppointments' => Appointment::today()->count(),
            'pendingAppointments' => Appointment::pending()->count(),
            'todayRevenue' => Payment::today()->paid()->sum('total'),
        ];
    });
}

// Invalidate on appointment/payment changes
public function invalidateStatsCache(): void
{
    Cache::forget('dashboard_stats');
}
```

---

### 3. Optimize Database Queries

**Add query scopes for common filters:**

```php
// app/Models/Appointment.php
public function scopeWithCommonRelations(Builder $query): Builder
{
    return $query->with(['user.profile', 'payments']);
}

public function scopeForListing(Builder $query): Builder
{
    return $query->select([
        'id', 'user_id', 'appointment_date', 'appointment_time',
        'status', 'notes', 'created_at'
    ]);
}

// Use in controller
$appointments = Appointment::forListing()
    ->withCommonRelations()
    ->paginate($perPage);
```

**Use chunking for large datasets:**
```php
// app/Services/ReportService.php
public function exportAppointments(string $fromDate, string $toDate): void
{
    $from = Carbon::parse($fromDate);
    $to = Carbon::parse($toDate);

    Appointment::whereBetween('appointment_date', [$from, $to])
        ->with(['user', 'payments'])
        ->chunk(100, function ($appointments) {
            foreach ($appointments as $appointment) {
                // Process appointment
            }
        });
}
```

---

### 4. Add Database Indexes for Common Queries

**Already covered in Phase 2, but verify:**
```bash
# Check query execution plans
php artisan tinker

# Test slow queries
DB::enableQueryLog();
Appointment::today()->with(['user', 'payments'])->get();
dd(DB::getQueryLog());
```

---

### 5. Optimize Frontend Rendering

**Add React.memo for expensive components:**

```typescript
// frontend/src/components/shared/AppointmentCard.tsx
import { memo } from 'react';

interface AppointmentCardProps {
  appointment: Appointment;
  onCancel?: (id: number) => void;
}

export const AppointmentCard = memo(function AppointmentCard({
  appointment,
  onCancel,
}: AppointmentCardProps) {
  return (
    <Card>
      {/* ... */}
    </Card>
  );
});

// Only re-render if appointment changes
AppointmentCard.displayName = 'AppointmentCard';
```

**Add useMemo for expensive calculations:**

```typescript
// frontend/src/app/(patient)/appointments/page.tsx
import { useMemo } from 'react';

export default function AppointmentsPage() {
  const { data: appointments } = useQuery(...);

  const filteredAppointments = useMemo(() => {
    if (!appointments?.data) return [];

    return appointments.data.filter((apt) => {
      if (selectedTab === 'all') return true;
      if (selectedTab === 'upcoming') {
        return ['pending', 'confirmed'].includes(apt.status);
      }
      if (selectedTab === 'past') {
        return ['completed', 'cancelled', 'no_show'].includes(apt.status);
      }
      return true;
    });
  }, [appointments?.data, selectedTab]);

  // ...
}
```

**Add useCallback for event handlers:**

```typescript
// frontend/src/app/(admin)/admin/appointments/page.tsx
import { useCallback } from 'react';

export default function AdminAppointmentsPage() {
  const handleConfirm = useCallback((id: number) => {
    confirmMutation.mutate(id);
  }, [confirmMutation]);

  const handleCancel = useCallback((id: number) => {
    cancelMutation.mutate(id);
  }, [cancelMutation]);

  const getStatusBadge = useCallback((status: string) => {
    switch (status) {
      case 'pending':
        return <Badge variant="warning">{t('status.pending')}</Badge>;
      // ...
    }
  }, [t]);

  // ...
}
```

---

### 6. Implement Virtual Scrolling for Long Lists

**Install react-virtual:**
```bash
cd frontend && npm install @tanstack/react-virtual
```

**Create virtualized list component:**
```typescript
// frontend/src/components/shared/VirtualizedList.tsx
import { useVirtualizer } from '@tanstack/react-virtual';
import { useRef } from 'react';

interface VirtualizedListProps<T> {
  items: T[];
  renderItem: (item: T, index: number) => React.ReactNode;
  itemHeight: number;
  containerHeight?: number;
}

export function VirtualizedList<T>({
  items,
  renderItem,
  itemHeight,
  containerHeight = 400,
}: VirtualizedListProps<T>) {
  const parentRef = useRef<HTMLDivElement>(null);

  const virtualizer = useVirtualizer({
    count: items.length,
    getScrollElement: () => parentRef.current,
    estimateSize: () => itemHeight,
    overscan: 5,
  });

  return (
    <div
      ref={parentRef}
      style={{ height: containerHeight, overflow: 'auto' }}
    >
      <div
        style={{
          height: `${virtualizer.getTotalSize()}px`,
          width: '100%',
          position: 'relative',
        }}
      >
        {virtualizer.getVirtualItems().map((virtualItem) => (
          <div
            key={virtualItem.key}
            style={{
              position: 'absolute',
              top: 0,
              left: 0,
              width: '100%',
              height: `${virtualItem.size}px`,
              transform: `translateY(${virtualItem.start}px)`,
            }}
          >
            {renderItem(items[virtualItem.index], virtualItem.index)}
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

### 7. Add API Response Caching Headers

**Create caching middleware:**
```php
// app/Http/Middleware/CacheApiResponse.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponse
{
    public function handle(Request $request, Closure $next, int $maxAge = 60): Response
    {
        $response = $next($request);

        // Only cache GET requests
        if ($request->isMethod('GET')) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
            $response->headers->set('ETag', md5($response->getContent()));
        }

        return $response;
    }
}
```

**Apply to read-only routes:**
```php
// routes/api.php
Route::middleware(['cache.api:300'])->group(function () {
    Route::get('/slots/dates', [SlotController::class, 'dates']);
    Route::get('/slots/{date}', [SlotController::class, 'slots']);
});
```

---

### 8. Optimize Image Loading

**Add next/image optimization:**
```typescript
// frontend/src/components/shared/Avatar.tsx
import Image from 'next/image';

interface AvatarProps {
  src: string | null;
  alt: string;
  size?: 'sm' | 'md' | 'lg';
}

const sizes = {
  sm: 32,
  md: 48,
  lg: 96,
};

export function Avatar({ src, alt, size = 'md' }: AvatarProps) {
  const dimension = sizes[size];

  if (!src) {
    return (
      <div
        className="rounded-full bg-gray-200 flex items-center justify-center"
        style={{ width: dimension, height: dimension }}
      >
        <span className="text-gray-500">
          {alt.charAt(0).toUpperCase()}
        </span>
      </div>
    );
  }

  return (
    <Image
      src={src}
      alt={alt}
      width={dimension}
      height={dimension}
      className="rounded-full object-cover"
      loading="lazy"
    />
  );
}
```

---

### 9. Add Lazy Loading for Heavy Components

```typescript
// frontend/src/app/(admin)/admin/reports/page.tsx
import dynamic from 'next/dynamic';

// Lazy load chart components
const RevenueChart = dynamic(
  () => import('@/components/charts/RevenueChart'),
  {
    loading: () => <ChartSkeleton />,
    ssr: false,
  }
);

const AppointmentsChart = dynamic(
  () => import('@/components/charts/AppointmentsChart'),
  {
    loading: () => <ChartSkeleton />,
    ssr: false,
  }
);
```

---

### 10. Implement Request Debouncing

```typescript
// frontend/src/hooks/useDebounce.ts
import { useState, useEffect } from 'react';

export function useDebounce<T>(value: T, delay: number): T {
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(timer);
    };
  }, [value, delay]);

  return debouncedValue;
}

// Usage in search component
const [searchTerm, setSearchTerm] = useState('');
const debouncedSearch = useDebounce(searchTerm, 300);

const { data } = useQuery({
  queryKey: ['patients', debouncedSearch],
  queryFn: () => adminApi.getPatients({ search: debouncedSearch }),
  enabled: debouncedSearch.length > 0,
});
```

---

## Performance Testing

```bash
# Backend - Enable query logging in development
# Add to .env
LOG_QUERIES=true

# Create query logging listener
php artisan make:listener LogQueryListener

# Run with timing
php artisan test --log-junit=test-results.xml

# Frontend - Analyze bundle
cd frontend && npm run build
npx @next/bundle-analyzer
```

**Add performance test:**
```php
// tests/Feature/PerformanceTest.php
public function test_dashboard_loads_within_acceptable_time(): void
{
    $this->actingAs($this->admin);

    $start = microtime(true);
    $response = $this->getJson('/api/admin/dashboard/stats');
    $duration = microtime(true) - $start;

    $response->assertOk();
    $this->assertLessThan(0.5, $duration, 'Dashboard should load in under 500ms');
}

public function test_appointment_list_has_limited_queries(): void
{
    // Create 50 appointments
    Appointment::factory()->count(50)->create();

    DB::enableQueryLog();

    $this->actingAs($this->admin);
    $this->getJson('/api/admin/appointments');

    $queries = DB::getQueryLog();

    // Should be less than 10 queries due to eager loading
    $this->assertLessThan(10, count($queries), 'Too many queries (N+1 issue)');
}
```

---

## Acceptance Criteria

- [ ] No N+1 query issues (verified with query logging)
- [ ] Dashboard loads in < 500ms
- [ ] Slot generation cached and fast
- [ ] Settings cached
- [ ] Large lists use virtualization
- [ ] Images lazy loaded
- [ ] Search debounced
- [ ] All tests pass

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `app/Http/Traits/EagerLoadsRelations.php` | Create |
| `app/Services/SlotGeneratorService.php` | Add cache tags |
| `app/Services/DashboardService.php` | Add caching |
| `app/Models/ClinicSetting.php` | Add caching |
| `app/Http/Middleware/CacheApiResponse.php` | Create |
| `frontend/src/components/shared/VirtualizedList.tsx` | Create |
| `frontend/src/components/shared/Avatar.tsx` | Create |
| `frontend/src/hooks/useDebounce.ts` | Create |
| Multiple controllers | Add eager loading |
| Multiple frontend components | Add memo/useMemo |
