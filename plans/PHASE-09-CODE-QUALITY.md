# Phase 9: Code Quality & Refactoring

## Priority: LOW
## Estimated Effort: 3-4 days
## Dependencies: Phase 8

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 9: Code Quality & Refactoring.

Read this file completely, then implement each section:
1. Extract shared components to reduce duplication
2. Extract shared utility functions
3. Add proper documentation
4. Fix code style inconsistencies
5. Remove dead code
6. Add proper error boundaries

After each change, run tests to ensure nothing breaks.
Maintain 100% backend test coverage and 80% frontend coverage.
```

---

## Checklist

### 1. Extract Shared Status Badge Component

**Issue:** Status badge logic duplicated across 4+ files.

**Create shared component:**
```typescript
// frontend/src/components/shared/StatusBadge.tsx
import { Badge } from '@/components/ui/badge';
import { useTranslations } from 'next-intl';
import { cn } from '@/lib/utils';

type StatusType = 'appointment' | 'payment';

interface StatusBadgeProps {
  status: string;
  type?: StatusType;
  className?: string;
}

const appointmentStatusConfig: Record<string, { variant: string; labelKey: string }> = {
  pending: { variant: 'warning', labelKey: 'status.pending' },
  confirmed: { variant: 'info', labelKey: 'status.confirmed' },
  completed: { variant: 'success', labelKey: 'status.completed' },
  cancelled: { variant: 'destructive', labelKey: 'status.cancelled' },
  no_show: { variant: 'secondary', labelKey: 'status.noShow' },
};

const paymentStatusConfig: Record<string, { variant: string; labelKey: string }> = {
  pending: { variant: 'warning', labelKey: 'payment.pending' },
  paid: { variant: 'success', labelKey: 'payment.paid' },
  refunded: { variant: 'secondary', labelKey: 'payment.refunded' },
};

export function StatusBadge({ status, type = 'appointment', className }: StatusBadgeProps) {
  const t = useTranslations();
  const config = type === 'payment' ? paymentStatusConfig : appointmentStatusConfig;
  const statusConfig = config[status] || { variant: 'default', labelKey: status };

  return (
    <Badge
      variant={statusConfig.variant as any}
      className={cn(className)}
    >
      {t(statusConfig.labelKey)}
    </Badge>
  );
}
```

**Update files to use shared component:**
- [ ] `frontend/src/app/(patient)/appointments/page.tsx`
- [ ] `frontend/src/app/(admin)/admin/appointments/page.tsx`
- [ ] `frontend/src/app/(admin)/admin/dashboard/page.tsx`
- [ ] `frontend/src/app/(patient)/dashboard/page.tsx`

---

### 2. Extract Empty State Component

**Create shared component:**
```typescript
// frontend/src/components/shared/EmptyState.tsx
import { LucideIcon } from 'lucide-react';
import { useTranslations } from 'next-intl';

interface EmptyStateProps {
  icon: LucideIcon;
  title?: string;
  description?: string;
  action?: React.ReactNode;
}

export function EmptyState({
  icon: Icon,
  title,
  description,
  action,
}: EmptyStateProps) {
  const t = useTranslations();

  return (
    <div className="flex flex-col items-center justify-center py-12 text-center">
      <Icon className="h-12 w-12 text-muted-foreground mb-4" />
      <h3 className="text-lg font-medium text-foreground">
        {title || t('common.noData')}
      </h3>
      {description && (
        <p className="text-sm text-muted-foreground mt-1 max-w-md">
          {description}
        </p>
      )}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}
```

---

### 3. Extract Card Skeleton Component

```typescript
// frontend/src/components/shared/CardSkeleton.tsx
import { Skeleton } from '@/components/ui/skeleton';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

interface CardSkeletonProps {
  hasHeader?: boolean;
  lines?: number;
}

export function CardSkeleton({ hasHeader = true, lines = 3 }: CardSkeletonProps) {
  return (
    <Card>
      {hasHeader && (
        <CardHeader>
          <Skeleton className="h-6 w-1/3" />
        </CardHeader>
      )}
      <CardContent className="space-y-3">
        {Array.from({ length: lines }).map((_, i) => (
          <Skeleton
            key={i}
            className="h-4"
            style={{ width: `${100 - i * 15}%` }}
          />
        ))}
      </CardContent>
    </Card>
  );
}

export function TableSkeleton({ rows = 5, cols = 4 }: { rows?: number; cols?: number }) {
  return (
    <div className="space-y-3">
      {/* Header */}
      <div className="flex gap-4">
        {Array.from({ length: cols }).map((_, i) => (
          <Skeleton key={i} className="h-8 flex-1" />
        ))}
      </div>
      {/* Rows */}
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="flex gap-4">
          {Array.from({ length: cols }).map((_, j) => (
            <Skeleton key={j} className="h-12 flex-1" />
          ))}
        </div>
      ))}
    </div>
  );
}
```

---

### 4. Create Date Formatting Utility

```typescript
// frontend/src/lib/utils/date.ts
import { format, formatDistanceToNow, parseISO, isToday, isTomorrow, isYesterday } from 'date-fns';
import { ar, enUS } from 'date-fns/locale';

const locales = {
  ar,
  en: enUS,
};

export function formatDate(
  date: string | Date,
  formatStr: string = 'PPP',
  locale: 'ar' | 'en' = 'ar'
): string {
  const parsed = typeof date === 'string' ? parseISO(date) : date;
  return format(parsed, formatStr, { locale: locales[locale] });
}

export function formatRelativeDate(
  date: string | Date,
  locale: 'ar' | 'en' = 'ar'
): string {
  const parsed = typeof date === 'string' ? parseISO(date) : date;

  if (isToday(parsed)) return locale === 'ar' ? 'اليوم' : 'Today';
  if (isTomorrow(parsed)) return locale === 'ar' ? 'غداً' : 'Tomorrow';
  if (isYesterday(parsed)) return locale === 'ar' ? 'أمس' : 'Yesterday';

  return formatDistanceToNow(parsed, { addSuffix: true, locale: locales[locale] });
}

export function formatTime(time: string): string {
  const [hours, minutes] = time.split(':');
  const hour = parseInt(hours, 10);
  const suffix = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${suffix}`;
}

export function formatDateTime(
  date: string,
  time: string,
  locale: 'ar' | 'en' = 'ar'
): string {
  const formattedDate = formatDate(date, 'PPP', locale);
  const formattedTime = formatTime(time);
  return `${formattedDate} - ${formattedTime}`;
}
```

---

### 5. Create Currency Formatting Utility

```typescript
// frontend/src/lib/utils/currency.ts
export function formatCurrency(
  amount: number,
  currency: string = 'EGP',
  locale: string = 'ar-EG'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount);
}

export function formatNumber(
  value: number,
  locale: string = 'ar-EG'
): string {
  return new Intl.NumberFormat(locale).format(value);
}

export function formatPercentage(
  value: number,
  locale: string = 'ar-EG'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'percent',
    minimumFractionDigits: 0,
    maximumFractionDigits: 1,
  }).format(value / 100);
}
```

---

### 6. Add Error Boundaries

```typescript
// frontend/src/components/shared/ErrorBoundary.tsx
'use client';

import React, { Component, ErrorInfo, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { AlertTriangle } from 'lucide-react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('Error caught by boundary:', error, errorInfo);
    this.props.onError?.(error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="flex flex-col items-center justify-center min-h-[400px] p-8">
          <AlertTriangle className="h-12 w-12 text-destructive mb-4" />
          <h2 className="text-lg font-semibold mb-2">حدث خطأ غير متوقع</h2>
          <p className="text-muted-foreground mb-4 text-center max-w-md">
            نعتذر عن هذا الخطأ. يرجى إعادة تحميل الصفحة أو المحاولة لاحقاً.
          </p>
          <Button onClick={() => window.location.reload()}>
            إعادة تحميل الصفحة
          </Button>
        </div>
      );
    }

    return this.props.children;
  }
}

// Query Error Boundary for React Query
export function QueryErrorBoundary({ children }: { children: ReactNode }) {
  return (
    <ErrorBoundary
      onError={(error) => {
        // Log to error tracking service
        console.error('Query Error:', error);
      }}
    >
      {children}
    </ErrorBoundary>
  );
}
```

**Use in layout:**
```typescript
// frontend/src/app/(admin)/admin/layout.tsx
import { ErrorBoundary } from '@/components/shared/ErrorBoundary';

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return (
    <AdminLayoutWrapper>
      <ErrorBoundary>
        {children}
      </ErrorBoundary>
    </AdminLayoutWrapper>
  );
}
```

---

### 7. Remove Hardcoded Strings

**Find and replace hardcoded Arabic strings:**

```typescript
// frontend/src/app/(admin)/admin/dashboard/page.tsx
// Replace:
<Badge>مؤكد</Badge>

// With:
<Badge>{t('status.confirmed')}</Badge>
```

**Add missing translation keys:**
```json
// frontend/src/i18n/messages/ar.json
{
  "status": {
    "pending": "معلق",
    "confirmed": "مؤكد",
    "completed": "مكتمل",
    "cancelled": "ملغي",
    "noShow": "لم يحضر"
  },
  "payment": {
    "pending": "معلق",
    "paid": "مدفوع",
    "refunded": "مسترد"
  },
  "errors": {
    "timeout": "انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.",
    "network": "خطأ في الاتصال. يرجى التحقق من اتصالك بالإنترنت.",
    "rateLimited": "الكثير من الطلبات. يرجى الانتظار والمحاولة مرة أخرى.",
    "unknown": "حدث خطأ غير متوقع."
  }
}
```

---

### 8. Add PHPDoc Comments to Backend Services

```php
// app/Services/AppointmentService.php

/**
 * Service for managing appointment operations.
 *
 * This service handles all appointment-related business logic including
 * booking, cancellation, confirmation, and status updates.
 */
class AppointmentService
{
    use LogsActivity;

    private ClinicSetting $settings;
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->settings = ClinicSetting::getInstance();
        $this->notificationService = $notificationService;
    }

    /**
     * Book a new appointment for a patient.
     *
     * @param User $patient The patient booking the appointment
     * @param Carbon $datetime The date and time of the appointment
     * @param string|null $notes Optional notes for the appointment
     *
     * @throws \InvalidArgumentException If the slot is not available
     * @throws \InvalidArgumentException If the patient has too many no-shows
     * @throws \InvalidArgumentException If the patient already has a pending appointment
     *
     * @return Appointment The created appointment
     */
    public function book(User $patient, Carbon $datetime, ?string $notes = null): Appointment
    {
        // ...
    }

    /**
     * Cancel an existing appointment.
     *
     * @param Appointment $appointment The appointment to cancel
     * @param string $cancelledBy Who cancelled ('patient' or 'admin')
     * @param string|null $reason Optional cancellation reason
     *
     * @throws \InvalidArgumentException If cancellation period has passed
     *
     * @return Appointment The cancelled appointment
     */
    public function cancel(Appointment $appointment, string $cancelledBy, ?string $reason = null): Appointment
    {
        // ...
    }
}
```

---

### 9. Add JSDoc Comments to Frontend Utilities

```typescript
// frontend/src/lib/utils/date.ts

/**
 * Format a date string or Date object to a localized format.
 *
 * @param date - The date to format (ISO string or Date object)
 * @param formatStr - The date-fns format string (default: 'PPP')
 * @param locale - The locale to use ('ar' or 'en')
 * @returns The formatted date string
 *
 * @example
 * formatDate('2025-01-15', 'PPP', 'ar') // "١٥ يناير ٢٠٢٥"
 * formatDate(new Date(), 'yyyy-MM-dd', 'en') // "2025-01-15"
 */
export function formatDate(
  date: string | Date,
  formatStr: string = 'PPP',
  locale: 'ar' | 'en' = 'ar'
): string {
  // ...
}
```

---

### 10. Extract OTP Verification Logic (Backend)

**Issue:** OTP verification duplicated in multiple places.

**Create OTP Service:**
```php
// app/Services/OtpService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OtpService
{
    private const TOKEN_EXPIRY_MINUTES = 60;

    /**
     * Generate and store an OTP for the given identifier.
     */
    public function generate(string $identifier): string
    {
        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $identifier],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        return $token;
    }

    /**
     * Verify an OTP for the given identifier.
     *
     * @throws \InvalidArgumentException If OTP is invalid or expired
     */
    public function verify(string $identifier, string $token): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $identifier)
            ->first();

        if (!$record) {
            throw new \InvalidArgumentException(__('رمز التحقق غير صالح'));
        }

        if (!Hash::check($token, $record->token)) {
            throw new \InvalidArgumentException(__('رمز التحقق غير صالح'));
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(self::TOKEN_EXPIRY_MINUTES)->isPast()) {
            throw new \InvalidArgumentException(__('رمز التحقق منتهي الصلاحية'));
        }

        return true;
    }

    /**
     * Delete OTP record after successful verification.
     */
    public function delete(string $identifier): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $identifier)
            ->delete();
    }
}
```

**Update AuthController to use OtpService:**
```php
// app/Http/Controllers/Api/AuthController.php
public function __construct(
    private OtpService $otpService
) {}

public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
{
    $token = $this->otpService->generate($request->phone);

    // TODO: Send via SMS
    if (app()->environment('local', 'testing')) {
        \Log::debug("OTP generated for phone ending in " . substr($request->phone, -4));
    }

    return response()->json([
        'success' => true,
        'message' => __('تم إرسال رمز التحقق'),
    ]);
}

public function verifyOtp(VerifyOtpRequest $request): JsonResponse
{
    try {
        $this->otpService->verify($request->phone, $request->otp);

        return response()->json([
            'success' => true,
            'message' => __('تم التحقق بنجاح'),
        ]);
    } catch (\InvalidArgumentException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}
```

---

### 11. Clean Up Unused Code

**Run analysis tools:**
```bash
# Backend - Find unused classes
composer require --dev nunomaduro/phpinsights
./vendor/bin/phpinsights analyse app/

# Frontend - Find unused exports
cd frontend
npm install --save-dev @typescript-eslint/eslint-plugin
npx eslint --report-unused-disable-directives
```

**Remove identified dead code manually after review.**

---

## Testing Requirements

```bash
# Backend
php artisan test

# Frontend
cd frontend && npm test

# Verify no regressions
```

---

## Acceptance Criteria

- [ ] Shared components extracted (StatusBadge, EmptyState, CardSkeleton)
- [ ] Utility functions extracted (date, currency)
- [ ] Error boundaries added
- [ ] Hardcoded strings replaced with translations
- [ ] PHPDoc comments added to services
- [ ] JSDoc comments added to utilities
- [ ] OTP logic extracted to service
- [ ] Dead code removed
- [ ] All tests pass

---

## Files Created/Modified Summary

| File | Changes |
|------|---------|
| `frontend/src/components/shared/StatusBadge.tsx` | Create |
| `frontend/src/components/shared/EmptyState.tsx` | Create |
| `frontend/src/components/shared/CardSkeleton.tsx` | Create |
| `frontend/src/components/shared/ErrorBoundary.tsx` | Create |
| `frontend/src/lib/utils/date.ts` | Create |
| `frontend/src/lib/utils/currency.ts` | Create |
| `frontend/src/i18n/messages/ar.json` | Update |
| `frontend/src/i18n/messages/en.json` | Update |
| `app/Services/OtpService.php` | Create |
| `app/Services/*.php` | Add PHPDoc |
| `app/Http/Controllers/Api/AuthController.php` | Use OtpService |
