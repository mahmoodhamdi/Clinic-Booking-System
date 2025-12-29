# Phase 5: Frontend Security & Type Safety

## Overview
هذه المرحلة تركز على تحسين الأمان في الـ Frontend وإصلاح مشاكل الـ TypeScript.

**الأولوية:** عالية
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 1

---

## Pre-requisites Checklist
- [ ] Phase 1 completed
- [ ] Frontend running: `cd frontend && npm run dev`
- [ ] All tests passing: `cd frontend && npm test`

---

## Milestone 5.1: Implement Content Security Policy

### المشكلة
الـ Frontend ليس لديه Content Security Policy مما يعرضه لـ XSS attacks.

### الملف المتأثر
`frontend/next.config.ts`

### المهام

#### Task 5.1.1: Add Security Headers to Next.js Config
```typescript
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",

  images: {
    remotePatterns: [
      {
        protocol: "http",
        hostname: "localhost",
        port: "8000",
        pathname: "/storage/**",
      },
      {
        protocol: "https",
        hostname: "*.example.com",
        pathname: "/storage/**",
      },
    ],
  },

  compiler: {
    removeConsole: process.env.NODE_ENV === "production",
  },

  experimental: {
    optimizePackageImports: ["lucide-react", "date-fns"],
  },

  async headers() {
    return [
      {
        source: "/:path*",
        headers: [
          {
            key: "X-DNS-Prefetch-Control",
            value: "on",
          },
          {
            key: "Strict-Transport-Security",
            value: "max-age=31536000; includeSubDomains",
          },
          {
            key: "X-Frame-Options",
            value: "SAMEORIGIN",
          },
          {
            key: "X-Content-Type-Options",
            value: "nosniff",
          },
          {
            key: "X-XSS-Protection",
            value: "1; mode=block",
          },
          {
            key: "Referrer-Policy",
            value: "strict-origin-when-cross-origin",
          },
          {
            key: "Permissions-Policy",
            value: "camera=(), microphone=(), geolocation=()",
          },
          {
            key: "Content-Security-Policy",
            value: [
              "default-src 'self'",
              "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Next.js requires unsafe-eval in dev
              "style-src 'self' 'unsafe-inline' fonts.googleapis.com",
              "font-src 'self' fonts.gstatic.com",
              "img-src 'self' data: blob: http://localhost:8000 https:",
              "connect-src 'self' http://localhost:8000 http://localhost:9000 ws://localhost:3000",
              "frame-ancestors 'none'",
              "base-uri 'self'",
              "form-action 'self'",
            ].join("; "),
          },
        ],
      },
    ];
  },
};

export default nextConfig;
```

### Verification
```bash
cd frontend && npm run build
# Check headers in browser dev tools
```

---

## Milestone 5.2: Create TypeScript Types

### المشكلة
الكود يستخدم `any` في أماكن كثيرة بدلاً من types محددة.

### الملف المتأثر
`frontend/src/types/index.ts`

### المهام

#### Task 5.2.1: Create Comprehensive Type Definitions
```typescript
// frontend/src/types/index.ts

// ============ Base Types ============

export type UserRole = 'admin' | 'secretary' | 'patient';
export type AppointmentStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
export type PaymentStatus = 'pending' | 'paid' | 'refunded';
export type PaymentMethod = 'cash' | 'card' | 'wallet';
export type BloodType = 'A+' | 'A-' | 'B+' | 'B-' | 'AB+' | 'AB-' | 'O+' | 'O-';
export type Gender = 'male' | 'female';

// ============ User Types ============

export interface User {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  role: UserRole;
  avatar_url: string | null;
  is_active: boolean;
  created_at: string;
}

export interface PatientProfile {
  id: number;
  user_id: number;
  date_of_birth: string | null;
  gender: Gender | null;
  blood_type: BloodType | null;
  address: string | null;
  allergies: string | null;
  chronic_diseases: string | null;
  current_medications: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  insurance_provider: string | null;
  insurance_number: string | null;
  notes: string | null;
}

export interface PatientWithProfile extends User {
  profile: PatientProfile | null;
}

export interface PatientStatistics {
  total_appointments: number;
  completed_appointments: number;
  cancelled_appointments: number;
  no_show_count: number;
  upcoming_appointments: number;
  last_visit: string | null;
}

// ============ Appointment Types ============

export interface Appointment {
  id: number;
  user_id: number;
  appointment_date: string;
  appointment_time: string;
  status: AppointmentStatus;
  status_label: string;
  notes: string | null;
  can_cancel: boolean;
  patient?: User;
  payment?: Payment;
  medical_record?: MedicalRecord;
  created_at: string;
}

export interface Slot {
  time: string;
  formatted_time: string;
  is_available: boolean;
}

export interface AvailableDate {
  date: string;
  day_name: string;
  day_name_ar: string;
  available_slots: number;
  total_slots: number;
}

// ============ Medical Types ============

export interface MedicalRecord {
  id: number;
  appointment_id: number;
  patient_id: number;
  diagnosis: string;
  symptoms: string | null;
  examination_notes: string | null;
  treatment_plan: string | null;
  blood_pressure: string | null;
  heart_rate: number | null;
  temperature: number | null;
  weight: number | null;
  height: number | null;
  follow_up_date: string | null;
  follow_up_notes: string | null;
  patient?: User;
  appointment?: Appointment;
  prescriptions?: Prescription[];
  attachments?: Attachment[];
  created_at: string;
}

export interface Prescription {
  id: number;
  medical_record_id: number;
  prescription_number: string;
  notes: string | null;
  is_dispensed: boolean;
  dispensed_at: string | null;
  items?: PrescriptionItem[];
  medical_record?: MedicalRecord;
  created_at: string;
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

export interface Attachment {
  id: number;
  attachable_type: string;
  attachable_id: number;
  file_name: string;
  file_path: string;
  file_type: string;
  file_size: number;
  download_url: string;
  created_at: string;
}

// ============ Payment Types ============

export interface Payment {
  id: number;
  appointment_id: number;
  amount: number;
  discount: number;
  total: number;
  method: PaymentMethod;
  status: PaymentStatus;
  status_label: string;
  notes: string | null;
  paid_at: string | null;
  appointment?: Appointment;
  created_at: string;
}

export interface PaymentStatistics {
  total_revenue: number;
  total_discounts: number;
  total_payments: number;
  by_method: {
    cash: number;
    card: number;
    wallet: number;
  };
  by_status: {
    pending: number;
    paid: number;
    refunded: number;
  };
}

// ============ Notification Types ============

export interface Notification {
  id: string;
  type: string;
  data: {
    title: string;
    message: string;
    action_url?: string;
  };
  read_at: string | null;
  created_at: string;
}

// ============ Settings Types ============

export interface ClinicSettings {
  clinic_name: string;
  clinic_phone: string;
  clinic_email: string | null;
  clinic_address: string | null;
  clinic_logo_url: string | null;
  slot_duration: number;
  max_patients_per_slot: number;
  advance_booking_days: number;
  cancellation_hours: number;
  consultation_fee: number;
}

export interface Schedule {
  id: number;
  day_of_week: number;
  day_name: string;
  day_name_ar: string;
  start_time: string;
  end_time: string;
  break_start: string | null;
  break_end: string | null;
  is_active: boolean;
}

export interface Vacation {
  id: number;
  date: string;
  reason: string | null;
  is_active: boolean;
}

// ============ Dashboard Types ============

export interface DashboardStats {
  total_patients: number;
  today_appointments: number;
  upcoming_appointments: number;
  monthly_revenue: number;
}

export interface DashboardAppointment {
  id: number;
  patient_name: string;
  patient_avatar: string | null;
  time: string;
  status: AppointmentStatus;
}

export interface RecentActivity {
  type: 'appointment' | 'payment' | 'medical_record';
  id: number;
  description: string;
  user_avatar: string | null;
  created_at: string;
}

// ============ Report Types ============

export interface AppointmentReport {
  period: {
    from: string;
    to: string;
  };
  summary: {
    total: number;
    completed: number;
    cancelled: number;
    no_show: number;
    pending: number;
    confirmed: number;
    completion_rate: number;
  };
  appointments: Appointment[];
}

export interface RevenueReport {
  period: {
    from: string;
    to: string;
  };
  summary: {
    total_revenue: number;
    total_discounts: number;
    net_revenue: number;
    total_payments: number;
    average_payment: number;
  };
  by_method: {
    cash: number;
    card: number;
    wallet: number;
  };
  daily_breakdown: Array<{
    date: string;
    revenue: number;
    count: number;
  }>;
}

// ============ API Response Types ============

export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

// ============ Form Types ============

export interface LoginFormData {
  phone: string;
  password: string;
}

export interface RegisterFormData {
  name: string;
  phone: string;
  email?: string;
  password: string;
  password_confirmation: string;
}

export interface BookingFormData {
  date: string;
  time: string;
  notes?: string;
}

export interface ProfileFormData {
  name: string;
  email?: string;
  date_of_birth?: string;
  gender?: Gender;
  address?: string;
}

export interface MedicalInfoFormData {
  blood_type?: BloodType;
  allergies?: string;
  chronic_diseases?: string;
  current_medications?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
}

export interface PasswordChangeFormData {
  current_password: string;
  password: string;
  password_confirmation: string;
}
```

### Verification
```bash
cd frontend && npx tsc --noEmit
```

---

## Milestone 5.3: Replace All `any` Types

### المشكلة
الـ pages تستخدم `any` في أماكن كثيرة.

### الملفات المتأثرة
1. `frontend/src/app/(patient)/appointments/page.tsx`
2. `frontend/src/app/(patient)/medical-records/page.tsx`
3. `frontend/src/app/(patient)/prescriptions/page.tsx`
4. `frontend/src/app/(admin)/admin/patients/page.tsx`
5. `frontend/src/app/(admin)/admin/appointments/page.tsx`

### المهام

#### Task 5.3.1: Fix Patient Appointments Page
الملف: `frontend/src/app/(patient)/appointments/page.tsx`

ابحث عن:
```typescript
records.data.map((record: any) => ...)
```

واستبدلها بـ:
```typescript
import type { Appointment } from '@/types';

// In the component
const { data: appointments } = useQuery<ApiResponse<Appointment[]>>({
  queryKey: ['appointments'],
  queryFn: () => appointmentsApi.getMyAppointments({}),
});

// In the map
appointments?.data.map((appointment: Appointment) => ...)
```

#### Task 5.3.2: Fix Medical Records Page
الملف: `frontend/src/app/(patient)/medical-records/page.tsx`

```typescript
import type { MedicalRecord, ApiResponse } from '@/types';

const { data: records } = useQuery<ApiResponse<MedicalRecord[]>>({
  queryKey: ['medical-records'],
  queryFn: () => patientApi.getMedicalRecords(),
});

// In the map
records?.data.map((record: MedicalRecord) => ...)
```

#### Task 5.3.3: Fix Prescriptions Page
الملف: `frontend/src/app/(patient)/prescriptions/page.tsx`

```typescript
import type { Prescription, ApiResponse } from '@/types';

const { data: prescriptions } = useQuery<ApiResponse<Prescription[]>>({
  queryKey: ['prescriptions'],
  queryFn: () => patientApi.getPrescriptions(),
});

// In the map
prescriptions?.data.map((prescription: Prescription) => ...)
```

#### Task 5.3.4: Fix Admin Patients Page
الملف: `frontend/src/app/(admin)/admin/patients/page.tsx`

```typescript
import type { PatientWithProfile, PaginatedResponse, PatientStatistics } from '@/types';

const { data: patients } = useQuery<PaginatedResponse<PatientWithProfile>>({
  queryKey: ['admin-patients', page, search],
  queryFn: () => adminApi.getPatients({ page, search }),
});

// In the map
patients?.data.map((patient: PatientWithProfile) => ...)
```

#### Task 5.3.5: Fix Admin Appointments Page
الملف: `frontend/src/app/(admin)/admin/appointments/page.tsx`

```typescript
import type { Appointment, PaginatedResponse } from '@/types';

const { data: appointments } = useQuery<PaginatedResponse<Appointment>>({
  queryKey: ['admin-appointments', filters],
  queryFn: () => adminApi.getAppointments(filters),
});

// In the map
appointments?.data.map((appointment: Appointment) => ...)
```

### Verification
```bash
cd frontend && npx tsc --noEmit
cd frontend && npm run lint
```

---

## Milestone 5.4: Enable Strict TypeScript Mode

### المشكلة
الـ TypeScript config ليس strict بما فيه الكفاية.

### الملف المتأثر
`frontend/tsconfig.json`

### المهام

#### Task 5.4.1: Update tsconfig.json
```json
{
  "compilerOptions": {
    "target": "ES2017",
    "lib": ["dom", "dom.iterable", "esnext"],
    "allowJs": true,
    "skipLibCheck": true,
    "strict": true,
    "strictNullChecks": true,
    "strictFunctionTypes": true,
    "strictBindCallApply": true,
    "strictPropertyInitialization": true,
    "noImplicitAny": true,
    "noImplicitThis": true,
    "noImplicitReturns": true,
    "noFallthroughCasesInSwitch": true,
    "noUncheckedIndexedAccess": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "exactOptionalPropertyTypes": false,
    "forceConsistentCasingInFileNames": true,
    "noEmit": true,
    "esModuleInterop": true,
    "module": "esnext",
    "moduleResolution": "bundler",
    "resolveJsonModule": true,
    "isolatedModules": true,
    "jsx": "preserve",
    "incremental": true,
    "plugins": [
      {
        "name": "next"
      }
    ],
    "paths": {
      "@/*": ["./src/*"]
    }
  },
  "include": ["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"],
  "exclude": ["node_modules"]
}
```

#### Task 5.4.2: Fix Any TypeScript Errors
بعد تفعيل strict mode، شغل:
```bash
cd frontend && npx tsc --noEmit
```

وأصلح كل الـ errors.

### Verification
```bash
cd frontend && npx tsc --noEmit
cd frontend && npm test
cd frontend && npm run build
```

---

## Post-Phase Checklist

### Tests
- [ ] All tests pass: `cd frontend && npm test`
- [ ] Build succeeds: `cd frontend && npm run build`
- [ ] TypeScript check: `cd frontend && npx tsc --noEmit`
- [ ] Lint check: `cd frontend && npm run lint`

### Security
- [ ] CSP headers visible in browser dev tools
- [ ] Security headers (X-Frame-Options, etc.) present
- [ ] No TypeScript `any` types remaining

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
cd frontend && npm test && npm run build && cd .. && git add -A && git commit -m "feat(security): implement Phase 5 - Frontend Security & Type Safety

- Add Content Security Policy headers
- Add security headers (X-Frame-Options, HSTS, etc.)
- Create comprehensive TypeScript types
- Replace all 'any' types with proper types
- Enable strict TypeScript mode

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
