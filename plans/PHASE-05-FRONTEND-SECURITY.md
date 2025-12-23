# Phase 5: Frontend Security & Type Safety

## Priority: HIGH
## Estimated Effort: 3-4 days
## Dependencies: Phase 1 (backend security must be done first)

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 5: Frontend Security & Type Safety.

Read this file completely, then implement each section:
1. Fix token storage security (move from localStorage to HttpOnly cookies)
2. Fix cookie security flags
3. Add proper TypeScript types (remove all 'any')
4. Add Zod validation for API responses
5. Fix CORS configuration in vercel.json
6. Add input sanitization

After each change, run: cd frontend && npm test
Ensure all tests pass.
```

---

## Checklist

### 1. Implement Secure Token Storage

**Issue:** Token stored in localStorage (XSS vulnerable) and cookies without HttpOnly.

**Strategy:** Backend should set HttpOnly cookies, frontend should not handle tokens directly.

**Files to modify (backend):**
- [ ] `app/Http/Controllers/Api/AuthController.php`

**Add cookie-based auth to backend:**
```php
// AuthController.php - login method
public function login(LoginRequest $request): JsonResponse
{
    // ... authentication logic ...

    $token = $user->createToken('auth-token')->plainTextToken;

    // Set HttpOnly cookie for browser clients
    $cookie = cookie(
        'auth_token',
        $token,
        60 * 24, // 24 hours
        '/',
        null,
        true, // secure (HTTPS only in production)
        true, // httpOnly
        false,
        'strict' // SameSite
    );

    return response()->json([
        'success' => true,
        'message' => __('تم تسجيل الدخول بنجاح'),
        'data' => [
            'user' => new UserResource($user),
            // Token still returned for mobile apps, but browser should use cookie
        ],
    ])->withCookie($cookie);
}

// logout method
public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'success' => true,
        'message' => __('تم تسجيل الخروج بنجاح'),
    ])->withCookie(cookie()->forget('auth_token'));
}
```

**Files to modify (frontend):**
- [ ] `frontend/src/lib/api/client.ts`
- [ ] `frontend/src/lib/stores/auth.ts`
- [ ] `frontend/src/app/(auth)/login/page.tsx`
- [ ] `frontend/src/app/(auth)/register/page.tsx`

**Update API client:**
```typescript
// frontend/src/lib/api/client.ts
import axios from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // IMPORTANT: Send cookies with requests
});

// Request interceptor
api.interceptors.request.use((config) => {
  // Get locale from localStorage (safe, not sensitive)
  if (typeof window !== 'undefined') {
    const locale = localStorage.getItem('locale') || 'ar';
    config.headers['Accept-Language'] = locale;
  }

  // No longer need to manually set Authorization header
  // Cookie will be sent automatically with withCredentials: true

  return config;
});

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear local user data and redirect
      if (typeof window !== 'undefined') {
        localStorage.removeItem('user');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);
```

**Update auth store:**
```typescript
// frontend/src/lib/stores/auth.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { authApi } from '@/lib/api/auth';
import type { User } from '@/types';

interface AuthState {
  user: User | null;
  isLoading: boolean;
  error: string | null;
  // Remove token from state - it's in HttpOnly cookie
  setUser: (user: User | null) => void;
  login: (phone: string, password: string) => Promise<User>;
  register: (data: RegisterData) => Promise<User>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      isLoading: false,
      error: null,

      setUser: (user) => set({ user }),

      login: async (phone, password) => {
        set({ isLoading: true, error: null });
        try {
          const response = await authApi.login({ phone, password });
          const user = response.data.user;
          set({ user, isLoading: false });
          return user;
        } catch (error: any) {
          const message = error.response?.data?.message || 'Login failed';
          set({ error: message, isLoading: false });
          throw error;
        }
      },

      logout: async () => {
        try {
          await authApi.logout();
        } finally {
          set({ user: null });
        }
      },

      fetchUser: async () => {
        try {
          const response = await authApi.me();
          set({ user: response.data });
        } catch {
          set({ user: null });
        }
      },

      clearError: () => set({ error: null }),
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({ user: state.user }), // Only persist user, not token
    }
  )
);
```

**Update login page - Remove manual cookie/localStorage handling:**
```typescript
// frontend/src/app/(auth)/login/page.tsx
const onSubmit = async (data: LoginFormData) => {
  try {
    await login(data.phone, data.password);
    // Cookie is set by server response
    // Just redirect
    router.push('/dashboard');
  } catch (error) {
    // Error is handled by store
  }
};
```

---

### 2. Remove Insecure Cookie Setting

**Files to modify:**
- [ ] `frontend/src/app/(auth)/login/page.tsx` (lines 50-55)
- [ ] `frontend/src/app/(auth)/register/page.tsx` (lines 49-58)

**Remove these lines completely:**
```typescript
// DELETE THESE - insecure cookie handling
document.cookie = `token=${token};path=/;max-age=86400`;
document.cookie = `user=${JSON.stringify(user)};path=/;max-age=86400`;
```

---

### 3. Add TypeScript Type Definitions

**Issue:** Excessive use of `any` type throughout the codebase.

**Create comprehensive types:**
```typescript
// frontend/src/types/api.ts
export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface User {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  role: 'admin' | 'secretary' | 'patient';
  avatar: string | null;
  is_active: boolean;
  date_of_birth: string | null;
  gender: 'male' | 'female' | null;
  address: string | null;
  phone_verified_at: string | null;
  created_at: string;
}

export interface PatientProfile {
  id: number;
  user_id: number;
  blood_type: string | null;
  allergies: string | null;
  chronic_diseases: string | null;
  current_medications: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  insurance_provider: string | null;
  insurance_number: string | null;
  notes: string | null;
}

export interface Appointment {
  id: number;
  user_id: number;
  appointment_date: string;
  appointment_time: string;
  status: AppointmentStatus;
  notes: string | null;
  admin_notes: string | null;
  cancelled_by: 'patient' | 'admin' | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  created_at: string;
  updated_at: string;
  patient?: User;
}

export type AppointmentStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';

export interface MedicalRecord {
  id: number;
  appointment_id: number;
  patient_id: number;
  diagnosis: string;
  symptoms: string | null;
  examination_notes: string | null;
  treatment_plan: string | null;
  follow_up_date: string | null;
  vital_signs: VitalSigns | null;
  created_at: string;
  prescriptions?: Prescription[];
  attachments?: Attachment[];
}

export interface VitalSigns {
  blood_pressure?: string;
  heart_rate?: number;
  temperature?: number;
  weight?: number;
  height?: number;
}

export interface Prescription {
  id: number;
  medical_record_id: number;
  valid_until: string | null;
  is_dispensed: boolean;
  dispensed_at: string | null;
  notes: string | null;
  created_at: string;
  items?: PrescriptionItem[];
}

export interface PrescriptionItem {
  id: number;
  prescription_id: number;
  medication_name: string;
  dosage: string;
  frequency: string;
  duration: string;
  instructions: string | null;
}

export interface Payment {
  id: number;
  appointment_id: number;
  amount: number;
  discount: number;
  total: number;
  method: 'cash' | 'card' | 'wallet';
  status: 'pending' | 'paid' | 'refunded';
  transaction_id: string | null;
  paid_at: string | null;
  notes: string | null;
  appointment?: Appointment;
}

export interface Notification {
  id: string;
  type: string;
  data: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

export interface Attachment {
  id: number;
  file_name: string;
  file_path: string;
  file_type: string;
  file_size: number;
  full_url: string;
  uploaded_by: number;
  created_at: string;
}

export interface DashboardStats {
  totalPatients: number;
  todayAppointments: number;
  pendingAppointments: number;
  todayRevenue: number;
  weeklyAppointments: number;
  monthlyRevenue: number;
}

export interface Schedule {
  id: number;
  day_of_week: number;
  start_time: string;
  end_time: string;
  break_start: string | null;
  break_end: string | null;
  is_active: boolean;
}

export interface Vacation {
  id: number;
  title: string;
  start_date: string;
  end_date: string;
  reason: string | null;
}

export interface ClinicSettings {
  id: number;
  clinic_name: string;
  clinic_address: string | null;
  clinic_phone: string | null;
  clinic_email: string | null;
  slot_duration: number;
  max_patients_per_slot: number;
  advance_booking_days: number;
  cancellation_hours: number;
  logo: string | null;
}
```

---

### 4. Update Components to Use Types

**Fix files with `any` types:**

```typescript
// frontend/src/app/(admin)/admin/patients/page.tsx
// Replace:
{appointments.data.map((apt: any) => (
// With:
{appointments.data.map((apt: Appointment) => (

// Replace:
{medicalRecords.data.map((record: any) => (
// With:
{medicalRecords.data.map((record: MedicalRecord) => (
```

**Create typed API functions:**
```typescript
// frontend/src/lib/api/admin.ts
import type {
  Appointment,
  MedicalRecord,
  Payment,
  Patient,
  Prescription,
  DashboardStats,
  PaginatedResponse,
  ApiResponse,
} from '@/types/api';

export const adminApi = {
  // Dashboard
  getDashboardStats: async (): Promise<ApiResponse<DashboardStats>> => {
    const response = await api.get<ApiResponse<DashboardStats>>('/admin/dashboard/stats');
    return response.data;
  },

  // Appointments
  getAppointments: async (params?: {
    status?: string;
    date?: string;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<Appointment>>> => {
    const response = await api.get<ApiResponse<PaginatedResponse<Appointment>>>(
      '/admin/appointments',
      { params }
    );
    return response.data;
  },

  // Patients
  getPatients: async (params?: {
    search?: string;
    status?: string;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<User>>> => {
    const response = await api.get<ApiResponse<PaginatedResponse<User>>>(
      '/admin/patients',
      { params }
    );
    return response.data;
  },

  // Medical Records
  getMedicalRecords: async (params?: {
    patient_id?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<MedicalRecord>>> => {
    const response = await api.get<ApiResponse<PaginatedResponse<MedicalRecord>>>(
      '/admin/medical-records',
      { params }
    );
    return response.data;
  },

  createMedicalRecord: async (data: CreateMedicalRecordData): Promise<ApiResponse<MedicalRecord>> => {
    const response = await api.post<ApiResponse<MedicalRecord>>('/admin/medical-records', data);
    return response.data;
  },

  updateMedicalRecord: async (id: number, data: UpdateMedicalRecordData): Promise<ApiResponse<MedicalRecord>> => {
    const response = await api.put<ApiResponse<MedicalRecord>>(`/admin/medical-records/${id}`, data);
    return response.data;
  },

  // ... continue for all methods
};

// Type definitions for request bodies
interface CreateMedicalRecordData {
  appointment_id: number;
  diagnosis: string;
  symptoms?: string;
  examination_notes?: string;
  treatment_plan?: string;
  follow_up_date?: string;
  vital_signs?: VitalSigns;
}

interface UpdateMedicalRecordData extends Partial<CreateMedicalRecordData> {}
```

---

### 5. Add Zod Validation for API Responses

**Create validation schemas:**
```typescript
// frontend/src/lib/validations/api-responses.ts
import { z } from 'zod';

export const userSchema = z.object({
  id: z.number(),
  name: z.string(),
  email: z.string().email().nullable(),
  phone: z.string(),
  role: z.enum(['admin', 'secretary', 'patient']),
  avatar: z.string().nullable(),
  is_active: z.boolean(),
  date_of_birth: z.string().nullable(),
  gender: z.enum(['male', 'female']).nullable(),
  address: z.string().nullable(),
  phone_verified_at: z.string().nullable(),
  created_at: z.string(),
});

export const appointmentSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  appointment_date: z.string(),
  appointment_time: z.string(),
  status: z.enum(['pending', 'confirmed', 'completed', 'cancelled', 'no_show']),
  notes: z.string().nullable(),
  admin_notes: z.string().nullable(),
  cancelled_by: z.enum(['patient', 'admin']).nullable(),
  cancelled_at: z.string().nullable(),
  cancellation_reason: z.string().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
  patient: userSchema.optional(),
});

export const apiResponseSchema = <T extends z.ZodType>(dataSchema: T) =>
  z.object({
    success: z.boolean(),
    message: z.string(),
    data: dataSchema,
  });

export const paginatedResponseSchema = <T extends z.ZodType>(itemSchema: T) =>
  z.object({
    data: z.array(itemSchema),
    current_page: z.number(),
    last_page: z.number(),
    per_page: z.number(),
    total: z.number(),
  });

// Validation helper
export function validateResponse<T>(schema: z.ZodType<T>, data: unknown): T {
  const result = schema.safeParse(data);
  if (!result.success) {
    console.error('API Response validation failed:', result.error);
    throw new Error('Invalid API response format');
  }
  return result.data;
}
```

**Use in API calls:**
```typescript
// frontend/src/lib/api/appointments.ts
import { validateResponse, apiResponseSchema, appointmentSchema } from '@/lib/validations/api-responses';

export const appointmentsApi = {
  getAppointment: async (id: number) => {
    const response = await api.get(`/appointments/${id}`);
    return validateResponse(
      apiResponseSchema(appointmentSchema),
      response.data
    );
  },
};
```

---

### 6. Fix CORS Configuration

**File:** `frontend/vercel.json`

**Current (problematic):**
```json
{ "key": "Access-Control-Allow-Origin", "value": "*" },
{ "key": "Access-Control-Allow-Credentials", "value": "true" }
```

**Fixed:**
```json
{
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        { "key": "X-DNS-Prefetch-Control", "value": "on" },
        { "key": "X-Content-Type-Options", "value": "nosniff" },
        { "key": "X-Frame-Options", "value": "SAMEORIGIN" },
        { "key": "X-XSS-Protection", "value": "1; mode=block" },
        { "key": "Referrer-Policy", "value": "strict-origin-when-cross-origin" }
      ]
    }
  ]
}
```

**Note:** CORS should be handled by the backend, not frontend headers.

---

### 7. Update Middleware for Cookie-Based Auth

**File:** `frontend/src/middleware.ts`

```typescript
// frontend/src/middleware.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Public paths that don't require auth
  const publicPaths = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-otp'];
  const isPublicPath = publicPaths.some(path => pathname.startsWith(path));

  // Admin-only paths
  const adminPaths = ['/admin'];
  const isAdminPath = adminPaths.some(path => pathname.startsWith(path));

  // Get user from cookie (set by server)
  const userCookie = request.cookies.get('user')?.value;
  let user = null;

  try {
    if (userCookie) {
      user = JSON.parse(userCookie);
    }
  } catch {
    // Invalid cookie
  }

  // Redirect to login if not authenticated on protected routes
  if (!isPublicPath && !user) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    return NextResponse.redirect(loginUrl);
  }

  // Redirect authenticated users away from auth pages
  if (isPublicPath && user) {
    const dashboardUrl = user.role === 'patient' ? '/dashboard' : '/admin/dashboard';
    return NextResponse.redirect(new URL(dashboardUrl, request.url));
  }

  // Check admin access
  if (isAdminPath && user?.role === 'patient') {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|public).*)',
  ],
};
```

---

### 8. Add XSS Protection for User Content

**Create sanitization utility:**
```typescript
// frontend/src/lib/utils/sanitize.ts
import DOMPurify from 'dompurify';

// For rich text content that needs to preserve some HTML
export function sanitizeHtml(dirty: string): string {
  if (typeof window === 'undefined') {
    // Server-side fallback
    return dirty.replace(/<[^>]*>/g, '');
  }
  return DOMPurify.sanitize(dirty, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'p', 'br'],
    ALLOWED_ATTR: [],
  });
}

// For plain text that should have no HTML
export function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
```

**Install DOMPurify:**
```bash
cd frontend && npm install dompurify @types/dompurify
```

---

## Testing Requirements

```bash
cd frontend

# Run unit tests
npm test

# Type check
npx tsc --noEmit

# Lint
npm run lint
```

**New tests to write:**

```typescript
// frontend/src/__tests__/lib/api-validation.test.ts
import { validateResponse, userSchema, apiResponseSchema } from '@/lib/validations/api-responses';

describe('API Response Validation', () => {
  test('validates valid user response', () => {
    const validResponse = {
      success: true,
      message: 'Success',
      data: {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        phone: '01234567890',
        role: 'patient',
        avatar: null,
        is_active: true,
        date_of_birth: null,
        gender: null,
        address: null,
        phone_verified_at: null,
        created_at: '2025-01-01T00:00:00.000Z',
      },
    };

    expect(() =>
      validateResponse(apiResponseSchema(userSchema), validResponse)
    ).not.toThrow();
  });

  test('throws on invalid response', () => {
    const invalidResponse = {
      success: true,
      message: 'Success',
      data: {
        id: 'not-a-number', // Should be number
        name: 'Test',
      },
    };

    expect(() =>
      validateResponse(apiResponseSchema(userSchema), invalidResponse)
    ).toThrow();
  });
});
```

---

## Acceptance Criteria

- [ ] Token stored in HttpOnly cookie (set by backend)
- [ ] No tokens in localStorage or JavaScript-accessible cookies
- [ ] All `any` types replaced with proper types
- [ ] API responses validated with Zod
- [ ] CORS headers properly configured
- [ ] XSS protection in place
- [ ] TypeScript compiles without errors
- [ ] All tests pass

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `app/Http/Controllers/Api/AuthController.php` | Add cookie auth |
| `frontend/src/lib/api/client.ts` | Add withCredentials, remove token |
| `frontend/src/lib/stores/auth.ts` | Remove token handling |
| `frontend/src/app/(auth)/login/page.tsx` | Remove cookie setting |
| `frontend/src/app/(auth)/register/page.tsx` | Remove cookie setting |
| `frontend/src/types/api.ts` | Create comprehensive types |
| `frontend/src/lib/api/admin.ts` | Add types to all methods |
| `frontend/src/lib/validations/api-responses.ts` | Create Zod schemas |
| `frontend/src/middleware.ts` | Update for cookie auth |
| `frontend/vercel.json` | Fix headers |
| `frontend/src/lib/utils/sanitize.ts` | Create |
