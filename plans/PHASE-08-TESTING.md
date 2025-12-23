# Phase 8: Testing Infrastructure

## Priority: MEDIUM
## Estimated Effort: 4-5 days
## Dependencies: Phase 6

---

## Prompt for Claude

```
I'm working on the Clinic Booking System. Please implement Phase 8: Testing Infrastructure.

Read this file completely, then implement each section:
1. Set up MSW (Mock Service Worker) for API mocking
2. Create test utilities and custom render functions
3. Create test factories/fixtures
4. Write comprehensive unit tests for API layer
5. Write tests for pages and components
6. Improve E2E tests
7. Set up CI/CD with GitHub Actions

Target: Increase frontend coverage from 4% to 80%+
After each change, run: cd frontend && npm test
```

---

## Checklist

### 1. Set Up MSW (Mock Service Worker)

**Install MSW:**
```bash
cd frontend
npm install msw --save-dev
```

**Create handlers:**
```typescript
// frontend/src/__tests__/mocks/handlers.ts
import { http, HttpResponse } from 'msw';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const handlers = [
  // Auth handlers
  http.post(`${API_URL}/auth/login`, async ({ request }) => {
    const body = await request.json() as { phone: string; password: string };

    if (body.phone === '01234567890' && body.password === 'password') {
      return HttpResponse.json({
        success: true,
        message: 'تم تسجيل الدخول بنجاح',
        data: {
          user: {
            id: 1,
            name: 'Test User',
            phone: '01234567890',
            email: null,
            role: 'patient',
            avatar: null,
            is_active: true,
          },
          token: 'mock-token-12345',
        },
      });
    }

    return HttpResponse.json(
      { success: false, message: 'بيانات الدخول غير صحيحة' },
      { status: 401 }
    );
  }),

  http.post(`${API_URL}/auth/logout`, () => {
    return HttpResponse.json({
      success: true,
      message: 'تم تسجيل الخروج بنجاح',
    });
  }),

  http.get(`${API_URL}/auth/me`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        id: 1,
        name: 'Test User',
        phone: '01234567890',
        role: 'patient',
      },
    });
  }),

  // Appointments handlers
  http.get(`${API_URL}/appointments`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        data: [
          {
            id: 1,
            appointment_date: '2025-01-15',
            appointment_time: '10:00:00',
            status: 'confirmed',
            notes: null,
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    });
  }),

  http.post(`${API_URL}/appointments`, async ({ request }) => {
    const body = await request.json();
    return HttpResponse.json({
      success: true,
      message: 'تم حجز الموعد بنجاح',
      data: {
        id: 2,
        ...body,
        status: 'pending',
        created_at: new Date().toISOString(),
      },
    }, { status: 201 });
  }),

  // Slots handlers
  http.get(`${API_URL}/slots/dates`, () => {
    return HttpResponse.json({
      success: true,
      data: [
        { date: '2025-01-15', day_name: 'الأربعاء', slots_count: 10 },
        { date: '2025-01-16', day_name: 'الخميس', slots_count: 8 },
      ],
    });
  }),

  http.get(`${API_URL}/slots/:date`, () => {
    return HttpResponse.json({
      success: true,
      data: [
        { time: '09:00', available: true },
        { time: '09:30', available: true },
        { time: '10:00', available: false },
      ],
    });
  }),

  // Admin handlers
  http.get(`${API_URL}/admin/dashboard/stats`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        totalPatients: 100,
        todayAppointments: 15,
        pendingAppointments: 5,
        todayRevenue: 5000,
      },
    });
  }),

  // ... add more handlers as needed
];
```

**Create MSW server:**
```typescript
// frontend/src/__tests__/mocks/server.ts
import { setupServer } from 'msw/node';
import { handlers } from './handlers';

export const server = setupServer(...handlers);
```

**Update jest.setup.js:**
```javascript
// frontend/jest.setup.js
import '@testing-library/jest-dom';
import { server } from './__tests__/mocks/server';

// Start server before all tests
beforeAll(() => server.listen({ onUnhandledRequest: 'warn' }));

// Reset handlers after each test
afterEach(() => server.resetHandlers());

// Clean up after all tests
afterAll(() => server.close());

// Existing mocks...
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
    back: jest.fn(),
    prefetch: jest.fn(),
  }),
  usePathname: () => '/',
  useSearchParams: () => new URLSearchParams(),
}));

// ... rest of existing mocks
```

---

### 2. Create Test Utilities

**Create custom render:**
```typescript
// frontend/src/__tests__/utils/test-utils.tsx
import React, { ReactElement } from 'react';
import { render, RenderOptions } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { NextIntlClientProvider } from 'next-intl';
import { ThemeProvider } from 'next-themes';

// Import messages
import messages from '@/i18n/messages/ar.json';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
    },
  },
});

interface WrapperProps {
  children: React.ReactNode;
}

function AllTheProviders({ children }: WrapperProps) {
  return (
    <QueryClientProvider client={queryClient}>
      <NextIntlClientProvider locale="ar" messages={messages}>
        <ThemeProvider attribute="class" defaultTheme="light">
          {children}
        </ThemeProvider>
      </NextIntlClientProvider>
    </QueryClientProvider>
  );
}

const customRender = (
  ui: ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>
) => render(ui, { wrapper: AllTheProviders, ...options });

// Re-export everything
export * from '@testing-library/react';
export { customRender as render };

// Helper for waiting for async operations
export const waitForLoadingToFinish = () =>
  new Promise((resolve) => setTimeout(resolve, 0));

// Helper for simulating user interactions
export { default as userEvent } from '@testing-library/user-event';
```

---

### 3. Create Test Factories

```typescript
// frontend/src/__tests__/factories/index.ts
import type { User, Appointment, MedicalRecord, Payment, Prescription } from '@/types/api';

let idCounter = 1;

export const createUser = (overrides: Partial<User> = {}): User => ({
  id: idCounter++,
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
  created_at: new Date().toISOString(),
  ...overrides,
});

export const createAdmin = (overrides: Partial<User> = {}): User =>
  createUser({ role: 'admin', name: 'Admin User', ...overrides });

export const createPatient = (overrides: Partial<User> = {}): User =>
  createUser({ role: 'patient', ...overrides });

export const createAppointment = (overrides: Partial<Appointment> = {}): Appointment => ({
  id: idCounter++,
  user_id: 1,
  appointment_date: '2025-01-15',
  appointment_time: '10:00:00',
  status: 'pending',
  notes: null,
  admin_notes: null,
  cancelled_by: null,
  cancelled_at: null,
  cancellation_reason: null,
  created_at: new Date().toISOString(),
  updated_at: new Date().toISOString(),
  ...overrides,
});

export const createMedicalRecord = (overrides: Partial<MedicalRecord> = {}): MedicalRecord => ({
  id: idCounter++,
  appointment_id: 1,
  patient_id: 1,
  diagnosis: 'Test diagnosis',
  symptoms: 'Test symptoms',
  examination_notes: null,
  treatment_plan: null,
  follow_up_date: null,
  vital_signs: null,
  created_at: new Date().toISOString(),
  ...overrides,
});

export const createPayment = (overrides: Partial<Payment> = {}): Payment => ({
  id: idCounter++,
  appointment_id: 1,
  amount: 100,
  discount: 0,
  total: 100,
  method: 'cash',
  status: 'pending',
  transaction_id: null,
  paid_at: null,
  notes: null,
  ...overrides,
});

export const createPrescription = (overrides: Partial<Prescription> = {}): Prescription => ({
  id: idCounter++,
  medical_record_id: 1,
  valid_until: null,
  is_dispensed: false,
  dispensed_at: null,
  notes: null,
  created_at: new Date().toISOString(),
  items: [],
  ...overrides,
});

// Reset counter for test isolation
export const resetFactoryCounter = () => {
  idCounter = 1;
};
```

---

### 4. Write API Layer Tests

```typescript
// frontend/src/__tests__/lib/api/auth.test.ts
import { authApi } from '@/lib/api/auth';
import { server } from '../../mocks/server';
import { http, HttpResponse } from 'msw';

describe('authApi', () => {
  describe('login', () => {
    it('returns user data on successful login', async () => {
      const result = await authApi.login({
        phone: '01234567890',
        password: 'password',
      });

      expect(result.success).toBe(true);
      expect(result.data.user.phone).toBe('01234567890');
    });

    it('throws error on invalid credentials', async () => {
      await expect(
        authApi.login({ phone: 'wrong', password: 'wrong' })
      ).rejects.toThrow();
    });
  });

  describe('me', () => {
    it('returns current user', async () => {
      const result = await authApi.me();

      expect(result.success).toBe(true);
      expect(result.data.id).toBe(1);
    });

    it('handles unauthorized error', async () => {
      server.use(
        http.get('*/auth/me', () => {
          return HttpResponse.json(
            { success: false, message: 'Unauthorized' },
            { status: 401 }
          );
        })
      );

      await expect(authApi.me()).rejects.toThrow();
    });
  });
});
```

```typescript
// frontend/src/__tests__/lib/api/appointments.test.ts
import { appointmentsApi } from '@/lib/api/appointments';
import { server } from '../../mocks/server';
import { http, HttpResponse } from 'msw';

describe('appointmentsApi', () => {
  describe('getAppointments', () => {
    it('returns paginated appointments', async () => {
      const result = await appointmentsApi.getAppointments();

      expect(result.success).toBe(true);
      expect(result.data.data).toHaveLength(1);
      expect(result.data.current_page).toBe(1);
    });
  });

  describe('bookAppointment', () => {
    it('creates a new appointment', async () => {
      const result = await appointmentsApi.bookAppointment({
        date: '2025-01-15',
        time: '10:00',
        notes: 'Test notes',
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe('pending');
    });

    it('handles slot not available error', async () => {
      server.use(
        http.post('*/appointments', () => {
          return HttpResponse.json(
            { success: false, message: 'Slot not available' },
            { status: 422 }
          );
        })
      );

      await expect(
        appointmentsApi.bookAppointment({
          date: '2025-01-15',
          time: '10:00',
        })
      ).rejects.toThrow();
    });
  });
});
```

---

### 5. Write Component Tests

```typescript
// frontend/src/__tests__/components/auth/LoginForm.test.tsx
import { render, screen, waitFor } from '../../utils/test-utils';
import userEvent from '@testing-library/user-event';
import LoginPage from '@/app/(auth)/login/page';

// Mock useRouter
const mockPush = jest.fn();
jest.mock('next/navigation', () => ({
  ...jest.requireActual('next/navigation'),
  useRouter: () => ({
    push: mockPush,
    replace: jest.fn(),
  }),
}));

describe('LoginPage', () => {
  beforeEach(() => {
    mockPush.mockClear();
  });

  it('renders login form', () => {
    render(<LoginPage />);

    expect(screen.getByLabelText(/phone/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /login/i })).toBeInTheDocument();
  });

  it('shows validation errors for empty fields', async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    await user.click(screen.getByRole('button', { name: /login/i }));

    await waitFor(() => {
      expect(screen.getByText(/phone is required/i)).toBeInTheDocument();
    });
  });

  it('submits form with valid data', async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    await user.type(screen.getByLabelText(/phone/i), '01234567890');
    await user.type(screen.getByLabelText(/password/i), 'password');
    await user.click(screen.getByRole('button', { name: /login/i }));

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/dashboard');
    });
  });

  it('shows error message on invalid credentials', async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    await user.type(screen.getByLabelText(/phone/i), 'wrong');
    await user.type(screen.getByLabelText(/password/i), 'wrong');
    await user.click(screen.getByRole('button', { name: /login/i }));

    await waitFor(() => {
      expect(screen.getByText(/invalid credentials/i)).toBeInTheDocument();
    });
  });
});
```

```typescript
// frontend/src/__tests__/components/appointments/AppointmentsList.test.tsx
import { render, screen, waitFor } from '../../utils/test-utils';
import AppointmentsPage from '@/app/(patient)/appointments/page';
import { createAppointment } from '../../factories';
import { server } from '../../mocks/server';
import { http, HttpResponse } from 'msw';

describe('AppointmentsPage', () => {
  it('shows loading state initially', () => {
    render(<AppointmentsPage />);
    expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
  });

  it('displays appointments when loaded', async () => {
    render(<AppointmentsPage />);

    await waitFor(() => {
      expect(screen.getByText('2025-01-15')).toBeInTheDocument();
    });
  });

  it('shows empty state when no appointments', async () => {
    server.use(
      http.get('*/appointments', () => {
        return HttpResponse.json({
          success: true,
          data: { data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 },
        });
      })
    );

    render(<AppointmentsPage />);

    await waitFor(() => {
      expect(screen.getByText(/no appointments/i)).toBeInTheDocument();
    });
  });

  it('filters appointments by tab', async () => {
    const appointments = [
      createAppointment({ id: 1, status: 'pending' }),
      createAppointment({ id: 2, status: 'completed' }),
    ];

    server.use(
      http.get('*/appointments', () => {
        return HttpResponse.json({
          success: true,
          data: { data: appointments, current_page: 1, last_page: 1, per_page: 15, total: 2 },
        });
      })
    );

    render(<AppointmentsPage />);

    // Wait for load
    await waitFor(() => {
      expect(screen.getAllByTestId('appointment-card')).toHaveLength(2);
    });

    // Click upcoming tab
    await userEvent.click(screen.getByRole('tab', { name: /upcoming/i }));

    await waitFor(() => {
      expect(screen.getAllByTestId('appointment-card')).toHaveLength(1);
    });
  });
});
```

---

### 6. Write Page Tests

```typescript
// frontend/src/__tests__/pages/booking.test.tsx
import { render, screen, waitFor } from '../utils/test-utils';
import userEvent from '@testing-library/user-event';
import BookingPage from '@/app/(patient)/book/page';

describe('BookingPage', () => {
  it('renders date selection', async () => {
    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByText('2025-01-15')).toBeInTheDocument();
    });
  });

  it('shows time slots when date selected', async () => {
    const user = userEvent.setup();
    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByText('2025-01-15')).toBeInTheDocument();
    });

    await user.click(screen.getByText('2025-01-15'));

    await waitFor(() => {
      expect(screen.getByText('09:00')).toBeInTheDocument();
    });
  });

  it('books appointment successfully', async () => {
    const user = userEvent.setup();
    render(<BookingPage />);

    // Select date
    await waitFor(() => screen.getByText('2025-01-15'));
    await user.click(screen.getByText('2025-01-15'));

    // Select time
    await waitFor(() => screen.getByText('09:00'));
    await user.click(screen.getByText('09:00'));

    // Submit
    await user.click(screen.getByRole('button', { name: /book/i }));

    await waitFor(() => {
      expect(screen.getByText(/booked successfully/i)).toBeInTheDocument();
    });
  });
});
```

---

### 7. Improve E2E Tests

```typescript
// frontend/e2e/auth.spec.ts
import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test.beforeEach(async ({ page }) => {
    // Reset to clean state
    await page.goto('/');
  });

  test('should redirect to login when not authenticated', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/.*login/);
  });

  test('should login with valid credentials', async ({ page }) => {
    await page.goto('/login');

    await page.fill('[name="phone"]', '01000000000');
    await page.fill('[name="password"]', 'admin123');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('should show error with invalid credentials', async ({ page }) => {
    await page.goto('/login');

    await page.fill('[name="phone"]', 'invalid');
    await page.fill('[name="password"]', 'invalid');
    await page.click('button[type="submit"]');

    await expect(page.locator('.error-message')).toBeVisible();
  });

  test('should logout successfully', async ({ page }) => {
    // First login
    await page.goto('/login');
    await page.fill('[name="phone"]', '01000000000');
    await page.fill('[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*dashboard/);

    // Then logout
    await page.click('[data-testid="user-menu"]');
    await page.click('[data-testid="logout-button"]');

    await expect(page).toHaveURL(/.*login/);
  });
});
```

```typescript
// frontend/e2e/booking.spec.ts
import { test, expect } from '@playwright/test';

test.describe('Appointment Booking', () => {
  test.beforeEach(async ({ page }) => {
    // Login as patient
    await page.goto('/login');
    await page.fill('[name="phone"]', '01234567890');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('should display available dates', async ({ page }) => {
    await page.goto('/book');

    await expect(page.locator('[data-testid="date-picker"]')).toBeVisible();
    await expect(page.locator('[data-testid="available-date"]').first()).toBeVisible();
  });

  test('should book an appointment', async ({ page }) => {
    await page.goto('/book');

    // Select first available date
    await page.click('[data-testid="available-date"]:first-child');

    // Select first available time
    await page.click('[data-testid="time-slot"]:not([disabled]):first-child');

    // Add notes
    await page.fill('[name="notes"]', 'Test appointment');

    // Submit
    await page.click('button[type="submit"]');

    // Expect success message
    await expect(page.locator('[data-testid="success-toast"]')).toBeVisible();
  });
});
```

---

### 8. Set Up GitHub Actions CI/CD

```yaml
# .github/workflows/test.yml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  backend-tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: clinic_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_mysql
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate key
        run: php artisan key:generate

      - name: Run tests
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: clinic_test
          DB_USERNAME: root
          DB_PASSWORD: password
        run: php artisan test --coverage --min=100

  frontend-tests:
    runs-on: ubuntu-latest

    defaults:
      run:
        working-directory: frontend

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Run linter
        run: npm run lint

      - name: Run type check
        run: npx tsc --noEmit

      - name: Run tests
        run: npm test -- --coverage --passWithNoTests

      - name: Upload coverage
        uses: codecov/codecov-action@v4
        with:
          files: frontend/coverage/lcov.info
          flags: frontend

  e2e-tests:
    runs-on: ubuntu-latest
    needs: [backend-tests, frontend-tests]

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install backend dependencies
        run: composer install --prefer-dist --no-progress

      - name: Install frontend dependencies
        working-directory: frontend
        run: npm ci

      - name: Install Playwright
        working-directory: frontend
        run: npx playwright install --with-deps

      - name: Setup environment
        run: |
          cp .env.example .env
          php artisan key:generate
          php artisan migrate:fresh --seed

      - name: Build frontend
        working-directory: frontend
        run: npm run build

      - name: Run E2E tests
        working-directory: frontend
        run: npm run test:e2e
        env:
          NEXT_PUBLIC_API_URL: http://localhost:8000/api

      - name: Upload test results
        uses: actions/upload-artifact@v4
        if: always()
        with:
          name: playwright-report
          path: frontend/playwright-report/
```

---

## Update jest.config.js

```javascript
// frontend/jest.config.js
const nextJest = require('next/jest');

const createJestConfig = nextJest({
  dir: './',
});

const customJestConfig = {
  setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],
  testEnvironment: 'jsdom',
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
  testPathIgnorePatterns: ['<rootDir>/node_modules/', '<rootDir>/e2e/'],
  collectCoverageFrom: [
    'src/**/*.{js,jsx,ts,tsx}',
    '!src/**/*.d.ts',
    '!src/**/index.ts',
    '!src/types/**/*',
    '!src/app/**/layout.tsx',
    '!src/app/**/loading.tsx',
    '!src/app/**/error.tsx',
  ],
  coverageThreshold: {
    global: {
      statements: 80,
      branches: 70,
      functions: 75,
      lines: 80,
    },
  },
};

module.exports = createJestConfig(customJestConfig);
```

---

## Acceptance Criteria

- [ ] MSW set up and working
- [ ] Custom render with providers
- [ ] Test factories created
- [ ] All API methods have tests
- [ ] Key components have tests
- [ ] Key pages have tests
- [ ] E2E tests reliable and comprehensive
- [ ] CI/CD pipeline working
- [ ] Frontend coverage >= 80%

---

## Files Created/Modified Summary

| File | Changes |
|------|---------|
| `frontend/src/__tests__/mocks/handlers.ts` | Create |
| `frontend/src/__tests__/mocks/server.ts` | Create |
| `frontend/src/__tests__/utils/test-utils.tsx` | Create |
| `frontend/src/__tests__/factories/index.ts` | Create |
| `frontend/src/__tests__/lib/api/*.test.ts` | Create |
| `frontend/src/__tests__/components/**/*.test.tsx` | Create |
| `frontend/src/__tests__/pages/**/*.test.tsx` | Create |
| `frontend/e2e/*.spec.ts` | Update |
| `frontend/jest.setup.js` | Update |
| `frontend/jest.config.js` | Update |
| `.github/workflows/test.yml` | Create |
