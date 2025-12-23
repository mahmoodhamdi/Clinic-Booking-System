# Phase 6: Frontend API Integration Fixes

## Priority: MEDIUM
## Estimated Effort: 2-3 days
## Dependencies: Phase 5

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 6: Frontend API Integration Fixes.

Read this file completely, then implement each section:
1. Fix API parameter naming mismatch (from/to vs from_date/to_date)
2. Add missing API endpoint functions
3. Replace mock data with real API calls
4. Add retry logic and timeout configuration
5. Improve error handling with specific messages
6. Add missing dashboard API integrations

After each change, run: cd frontend && npm test
Ensure all tests pass.
```

---

## Checklist

### 1. Fix API Parameter Naming Mismatch

**Issue:** Frontend sends `from`, `to` but backend expects `from_date`, `to_date`.

**File:** `frontend/src/lib/api/admin.ts` (lines 343-372)

**Current (broken):**
```typescript
getAppointmentsReport: async (from?: string, to?: string) => {
  const response = await api.get('/admin/reports/appointments', {
    params: { from, to }, // WRONG
  });
  return response.data;
},
```

**Fixed:**
```typescript
// frontend/src/lib/api/admin.ts

// Reports
getAppointmentsReport: async (fromDate?: string, toDate?: string, status?: string): Promise<ApiResponse<AppointmentsReport>> => {
  const response = await api.get<ApiResponse<AppointmentsReport>>('/admin/reports/appointments', {
    params: {
      from_date: fromDate,
      to_date: toDate,
      status,
    },
  });
  return response.data;
},

getRevenueReport: async (fromDate?: string, toDate?: string): Promise<ApiResponse<RevenueReport>> => {
  const response = await api.get<ApiResponse<RevenueReport>>('/admin/reports/revenue', {
    params: {
      from_date: fromDate,
      to_date: toDate,
    },
  });
  return response.data;
},

getPatientsReport: async (fromDate?: string, toDate?: string): Promise<ApiResponse<PatientsReport>> => {
  const response = await api.get<ApiResponse<PatientsReport>>('/admin/reports/patients', {
    params: {
      from_date: fromDate,
      to_date: toDate,
    },
  });
  return response.data;
},

exportAppointmentsReport: async (fromDate?: string, toDate?: string, status?: string): Promise<Blob> => {
  const response = await api.get('/admin/reports/appointments/export', {
    params: {
      from_date: fromDate,
      to_date: toDate,
      status,
    },
    responseType: 'blob',
  });
  return response.data;
},

exportRevenueReport: async (fromDate?: string, toDate?: string): Promise<Blob> => {
  const response = await api.get('/admin/reports/revenue/export', {
    params: {
      from_date: fromDate,
      to_date: toDate,
    },
    responseType: 'blob',
  });
  return response.data;
},

// ADD MISSING FUNCTION
exportPatientsReport: async (fromDate?: string, toDate?: string): Promise<Blob> => {
  const response = await api.get('/admin/reports/patients/export', {
    params: {
      from_date: fromDate,
      to_date: toDate,
    },
    responseType: 'blob',
  });
  return response.data;
},
```

---

### 2. Add Missing API Endpoint Functions

**File:** `frontend/src/lib/api/admin.ts`

**Missing functions to add:**

```typescript
// Dashboard endpoints
getDashboardToday: async (): Promise<ApiResponse<TodayStats>> => {
  const response = await api.get<ApiResponse<TodayStats>>('/admin/dashboard/today');
  return response.data;
},

getDashboardWeekly: async (): Promise<ApiResponse<WeeklyStats>> => {
  const response = await api.get<ApiResponse<WeeklyStats>>('/admin/dashboard/weekly');
  return response.data;
},

getDashboardMonthly: async (month?: number, year?: number): Promise<ApiResponse<MonthlyStats>> => {
  const response = await api.get<ApiResponse<MonthlyStats>>('/admin/dashboard/monthly', {
    params: { month, year },
  });
  return response.data;
},

getDashboardChart: async (period?: string): Promise<ApiResponse<ChartData>> => {
  const response = await api.get<ApiResponse<ChartData>>('/admin/dashboard/chart', {
    params: { period },
  });
  return response.data;
},

getRecentActivity: async (limit?: number): Promise<ApiResponse<Activity[]>> => {
  const response = await api.get<ApiResponse<Activity[]>>('/admin/dashboard/recent-activity', {
    params: { limit },
  });
  return response.data;
},

getUpcomingAppointments: async (limit?: number): Promise<ApiResponse<Appointment[]>> => {
  const response = await api.get<ApiResponse<Appointment[]>>('/admin/dashboard/upcoming-appointments', {
    params: { limit },
  });
  return response.data;
},

// Appointment endpoints
getTodayAppointments: async (): Promise<ApiResponse<PaginatedResponse<Appointment>>> => {
  const response = await api.get<ApiResponse<PaginatedResponse<Appointment>>>('/admin/appointments/today');
  return response.data;
},

getUpcomingAppointmentsAdmin: async (): Promise<ApiResponse<PaginatedResponse<Appointment>>> => {
  const response = await api.get<ApiResponse<PaginatedResponse<Appointment>>>('/admin/appointments/upcoming');
  return response.data;
},

getAppointmentsForDate: async (date: string): Promise<ApiResponse<Appointment[]>> => {
  const response = await api.get<ApiResponse<Appointment[]>>('/admin/appointments/for-date', {
    params: { date },
  });
  return response.data;
},

getAppointmentStatistics: async (): Promise<ApiResponse<AppointmentStats>> => {
  const response = await api.get<ApiResponse<AppointmentStats>>('/admin/appointments/statistics');
  return response.data;
},

// Patient endpoints
getPatientSummary: async (): Promise<ApiResponse<PatientSummary>> => {
  const response = await api.get<ApiResponse<PatientSummary>>('/admin/patients/summary');
  return response.data;
},

getPatientStatistics: async (id: number): Promise<ApiResponse<PatientStats>> => {
  const response = await api.get<ApiResponse<PatientStats>>(`/admin/patients/${id}/statistics`);
  return response.data;
},

togglePatientStatus: async (id: number): Promise<ApiResponse<User>> => {
  const response = await api.put<ApiResponse<User>>(`/admin/patients/${id}/status`);
  return response.data;
},

addPatientNotes: async (id: number, notes: string): Promise<ApiResponse<User>> => {
  const response = await api.post<ApiResponse<User>>(`/admin/patients/${id}/notes`, { notes });
  return response.data;
},

// Medical Records
getFollowUpsDue: async (): Promise<ApiResponse<MedicalRecord[]>> => {
  const response = await api.get<ApiResponse<MedicalRecord[]>>('/admin/medical-records/follow-ups-due');
  return response.data;
},

// Payments
getPaymentReport: async (params?: {
  from_date?: string;
  to_date?: string;
  method?: string;
}): Promise<ApiResponse<PaymentReport>> => {
  const response = await api.get<ApiResponse<PaymentReport>>('/admin/payments/report', { params });
  return response.data;
},

getAppointmentPayment: async (appointmentId: number): Promise<ApiResponse<Payment | null>> => {
  const response = await api.get<ApiResponse<Payment | null>>(`/admin/appointments/${appointmentId}/payment`);
  return response.data;
},

// Prescriptions
streamPrescriptionPdf: async (id: number): Promise<Blob> => {
  const response = await api.get(`/admin/prescriptions/${id}/pdf`, {
    responseType: 'blob',
  });
  return response.data;
},
```

**Add type definitions:**

```typescript
// frontend/src/types/api.ts (add these)
export interface TodayStats {
  appointments: number;
  confirmed: number;
  pending: number;
  completed: number;
  revenue: number;
}

export interface WeeklyStats {
  appointments: number[];
  revenue: number[];
  labels: string[];
}

export interface MonthlyStats {
  totalAppointments: number;
  totalRevenue: number;
  newPatients: number;
  averagePerDay: number;
}

export interface ChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
  }[];
}

export interface Activity {
  id: number;
  type: 'appointment' | 'payment' | 'record';
  description: string;
  created_at: string;
  user?: User;
}

export interface AppointmentStats {
  today: { total: number; pending: number; confirmed: number; completed: number };
  week: { total: number; pending: number; confirmed: number; completed: number };
  month: { total: number; pending: number; confirmed: number; completed: number };
}

export interface PatientSummary {
  total: number;
  active: number;
  inactive: number;
  newThisMonth: number;
}

export interface PatientStats {
  totalAppointments: number;
  completedAppointments: number;
  cancelledAppointments: number;
  noShowAppointments: number;
  totalPayments: number;
  pendingPayments: number;
}

export interface AppointmentsReport {
  summary: {
    total: number;
    completed: number;
    cancelled: number;
    noShow: number;
  };
  byStatus: Record<string, number>;
  byDay: { date: string; count: number }[];
}

export interface RevenueReport {
  summary: {
    total: number;
    paid: number;
    pending: number;
    refunded: number;
  };
  byMethod: Record<string, number>;
  byDay: { date: string; amount: number }[];
}

export interface PatientsReport {
  summary: {
    total: number;
    new: number;
    returning: number;
  };
  byGender: Record<string, number>;
  byAge: { range: string; count: number }[];
}

export interface PaymentReport {
  summary: {
    totalAmount: number;
    totalPaid: number;
    totalPending: number;
    totalRefunded: number;
  };
  byMethod: Record<string, number>;
  transactions: Payment[];
}
```

---

### 3. Replace Mock Data with Real API Calls

**File:** `frontend/src/app/(admin)/admin/dashboard/page.tsx`

**Current (mock data):**
```typescript
const stats = {
  totalPatients: 156,
  todayAppointments: 12,
  pendingAppointments: 5,
  todayRevenue: 2500,
};
```

**Fixed (real API):**
```typescript
'use client';

import { useQuery } from '@tanstack/react-query';
import { adminApi } from '@/lib/api/admin';
import { Skeleton } from '@/components/ui/skeleton';

export default function AdminDashboardPage() {
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboardStats'],
    queryFn: () => adminApi.getDashboardStats(),
  });

  const { data: todayData, isLoading: todayLoading } = useQuery({
    queryKey: ['dashboardToday'],
    queryFn: () => adminApi.getDashboardToday(),
  });

  const { data: recentActivity, isLoading: activityLoading } = useQuery({
    queryKey: ['recentActivity'],
    queryFn: () => adminApi.getRecentActivity(10),
  });

  const { data: upcomingAppointments, isLoading: upcomingLoading } = useQuery({
    queryKey: ['upcomingAppointments'],
    queryFn: () => adminApi.getUpcomingAppointments(5),
  });

  if (statsLoading || todayLoading) {
    return <DashboardSkeleton />;
  }

  return (
    <div className="p-6">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
          title={t('admin.dashboard.totalPatients')}
          value={stats?.data?.totalPatients ?? 0}
          icon={<Users className="h-6 w-6" />}
        />
        <StatCard
          title={t('admin.dashboard.todayAppointments')}
          value={todayData?.data?.appointments ?? 0}
          icon={<Calendar className="h-6 w-6" />}
        />
        {/* ... other cards */}
      </div>

      {/* Recent Activity */}
      <div className="mt-8">
        <h2>{t('admin.dashboard.recentActivity')}</h2>
        {activityLoading ? (
          <ActivitySkeleton />
        ) : (
          <ActivityList activities={recentActivity?.data ?? []} />
        )}
      </div>

      {/* Upcoming Appointments */}
      <div className="mt-8">
        <h2>{t('admin.dashboard.upcomingAppointments')}</h2>
        {upcomingLoading ? (
          <AppointmentsSkeleton />
        ) : (
          <AppointmentsList appointments={upcomingAppointments?.data ?? []} />
        )}
      </div>
    </div>
  );
}
```

---

### 4. Add Retry Logic and Timeout Configuration

**File:** `frontend/src/lib/api/client.ts`

```typescript
import axios, { AxiosError } from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// Retry configuration
const MAX_RETRIES = 3;
const RETRY_DELAY = 1000;

// Create axios instance with timeout
export const api = axios.create({
  baseURL: API_URL,
  timeout: 30000, // 30 seconds
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// Retry interceptor
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as any;

    // Don't retry if no config or already retried max times
    if (!config || config.__retryCount >= MAX_RETRIES) {
      return Promise.reject(error);
    }

    // Don't retry for certain status codes
    const noRetryStatuses = [400, 401, 403, 404, 422];
    if (error.response && noRetryStatuses.includes(error.response.status)) {
      return Promise.reject(error);
    }

    // Don't retry for non-idempotent methods (unless explicitly allowed)
    const idempotentMethods = ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];
    if (!idempotentMethods.includes(config.method?.toUpperCase() ?? '')) {
      return Promise.reject(error);
    }

    // Increment retry count
    config.__retryCount = (config.__retryCount || 0) + 1;

    // Calculate delay with exponential backoff
    const delay = RETRY_DELAY * Math.pow(2, config.__retryCount - 1);

    // Wait and retry
    await new Promise((resolve) => setTimeout(resolve, delay));

    console.log(`Retrying request (${config.__retryCount}/${MAX_RETRIES}): ${config.url}`);

    return api(config);
  }
);
```

---

### 5. Improve Error Handling with Specific Messages

**File:** `frontend/src/lib/api/client.ts`

```typescript
// Error handling interceptor
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    // Handle network errors
    if (!error.response) {
      if (error.code === 'ECONNABORTED') {
        throw new ApiError('Request timeout. Please try again.', 'TIMEOUT');
      }
      throw new ApiError('Network error. Please check your connection.', 'NETWORK');
    }

    const status = error.response.status;
    const data = error.response.data as any;

    // Handle specific status codes
    switch (status) {
      case 401:
        if (typeof window !== 'undefined') {
          localStorage.removeItem('user');
          window.location.href = '/login';
        }
        throw new ApiError('Session expired. Please login again.', 'UNAUTHORIZED');

      case 403:
        throw new ApiError('You do not have permission to perform this action.', 'FORBIDDEN');

      case 404:
        throw new ApiError('The requested resource was not found.', 'NOT_FOUND');

      case 422:
        throw new ValidationError(
          data.message || 'Validation failed.',
          data.errors || {}
        );

      case 429:
        throw new ApiError('Too many requests. Please wait and try again.', 'RATE_LIMITED');

      case 500:
      case 502:
      case 503:
        throw new ApiError('Server error. Please try again later.', 'SERVER_ERROR');

      default:
        throw new ApiError(
          data.message || 'An unexpected error occurred.',
          'UNKNOWN'
        );
    }
  }
);

// Custom error classes
export class ApiError extends Error {
  constructor(
    message: string,
    public code: string,
    public statusCode?: number
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export class ValidationError extends ApiError {
  constructor(
    message: string,
    public errors: Record<string, string[]>
  ) {
    super(message, 'VALIDATION', 422);
    this.name = 'ValidationError';
  }
}
```

**Update components to use specific errors:**

```typescript
// Example in a component
import { ApiError, ValidationError } from '@/lib/api/client';

const mutation = useMutation({
  mutationFn: (data) => adminApi.createAppointment(data),
  onError: (error) => {
    if (error instanceof ValidationError) {
      // Show field-specific errors
      Object.entries(error.errors).forEach(([field, messages]) => {
        toast.error(`${field}: ${messages.join(', ')}`);
      });
    } else if (error instanceof ApiError) {
      // Show appropriate message based on error code
      switch (error.code) {
        case 'TIMEOUT':
          toast.error(t('errors.timeout'));
          break;
        case 'NETWORK':
          toast.error(t('errors.network'));
          break;
        case 'RATE_LIMITED':
          toast.error(t('errors.rateLimited'));
          break;
        default:
          toast.error(error.message);
      }
    } else {
      toast.error(t('errors.unknown'));
    }
  },
});
```

---

### 6. Configure React Query Properly

**File:** `frontend/src/components/providers/index.tsx`

```typescript
'use client';

import { useState } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { ApiError } from '@/lib/api/client';

export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 5 * 60 * 1000, // 5 minutes
            gcTime: 10 * 60 * 1000, // 10 minutes (formerly cacheTime)
            refetchOnWindowFocus: false,
            retry: (failureCount, error) => {
              // Don't retry on auth errors
              if (error instanceof ApiError) {
                if (['UNAUTHORIZED', 'FORBIDDEN', 'NOT_FOUND'].includes(error.code)) {
                  return false;
                }
              }
              return failureCount < 2;
            },
            retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
          },
          mutations: {
            retry: false,
          },
        },
      })
  );

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {process.env.NODE_ENV === 'development' && (
        <ReactQueryDevtools initialIsOpen={false} />
      )}
    </QueryClientProvider>
  );
}
```

---

### 7. Fix FormData Content-Type Issue

**Issue:** Manually setting `Content-Type: multipart/form-data` breaks file uploads.

**Files to fix:**
- [ ] `frontend/src/lib/api/auth.ts` (line 98)
- [ ] `frontend/src/lib/api/admin.ts` (lines 136, 255)

**Current (broken):**
```typescript
const response = await api.post('/auth/avatar', formData, {
  headers: {
    'Content-Type': 'multipart/form-data', // WRONG - breaks boundary
  },
});
```

**Fixed:**
```typescript
// Let Axios auto-detect Content-Type for FormData
const response = await api.post('/auth/avatar', formData);
```

---

### 8. Update Patient History API Types

**File:** `frontend/src/lib/api/patient.ts`

**Current:**
```typescript
getHistory: async (): Promise<ApiResponse<unknown>> => {
  const response = await api.get<ApiResponse<unknown>>('/patient/history');
  return response.data;
},
```

**Fixed:**
```typescript
interface PatientHistory {
  appointments: Appointment[];
  medicalRecords: MedicalRecord[];
  prescriptions: Prescription[];
}

getHistory: async (): Promise<ApiResponse<PatientHistory>> => {
  const response = await api.get<ApiResponse<PatientHistory>>('/patient/history');
  return response.data;
},

getStatistics: async (): Promise<ApiResponse<PatientStats>> => {
  const response = await api.get<ApiResponse<PatientStats>>('/patient/statistics');
  return response.data;
},
```

---

## Testing Requirements

```bash
cd frontend

# Type check
npx tsc --noEmit

# Run tests
npm test

# Manual testing - verify API calls
# 1. Open browser dev tools
# 2. Go to Network tab
# 3. Navigate through admin dashboard
# 4. Verify correct API calls are made with proper parameters
```

**New tests:**

```typescript
// frontend/src/__tests__/lib/api/admin.test.ts
import { adminApi } from '@/lib/api/admin';
import { api } from '@/lib/api/client';

jest.mock('@/lib/api/client');

describe('adminApi', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getAppointmentsReport', () => {
    it('sends correct parameter names', async () => {
      (api.get as jest.Mock).mockResolvedValue({ data: { success: true, data: {} } });

      await adminApi.getAppointmentsReport('2025-01-01', '2025-01-31', 'confirmed');

      expect(api.get).toHaveBeenCalledWith('/admin/reports/appointments', {
        params: {
          from_date: '2025-01-01',
          to_date: '2025-01-31',
          status: 'confirmed',
        },
      });
    });
  });

  describe('exportPatientsReport', () => {
    it('exists and is callable', async () => {
      expect(typeof adminApi.exportPatientsReport).toBe('function');
    });
  });
});
```

---

## Acceptance Criteria

- [ ] All API parameters correctly named
- [ ] All missing API functions added
- [ ] Dashboard uses real API data (no mock data)
- [ ] Retry logic working for transient failures
- [ ] Timeout configured at 30 seconds
- [ ] Error handling shows specific messages
- [ ] File uploads working (FormData fix)
- [ ] TypeScript compiles without errors
- [ ] All tests pass

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `frontend/src/lib/api/admin.ts` | Fix params, add missing functions |
| `frontend/src/lib/api/patient.ts` | Add types |
| `frontend/src/lib/api/auth.ts` | Fix FormData |
| `frontend/src/lib/api/client.ts` | Add retry, timeout, error handling |
| `frontend/src/types/api.ts` | Add new types |
| `frontend/src/components/providers/index.tsx` | Configure QueryClient |
| `frontend/src/app/(admin)/admin/dashboard/page.tsx` | Replace mock data |
| `frontend/src/__tests__/lib/api/admin.test.ts` | Add tests |
