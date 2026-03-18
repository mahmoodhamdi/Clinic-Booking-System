# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Clinic Booking System for a single doctor's private clinic built with **Laravel 12** backend and **Next.js 16** frontend. The system serves three user types: Admin (Doctor), Secretary, and Patients.

## Build & Development Commands

### Initial Setup
```bash
# Backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed

# Frontend
cd frontend && npm install
```

### Running Development Servers
```bash
# Backend (runs API server + queue worker + pail logs + vite concurrently via npx concurrently)
composer dev

# Frontend (in separate terminal)
cd frontend && npm run dev
```

Backend runs on localhost:8000, Frontend on localhost:3000.

**Important**: Frontend `client.ts` falls back to port 9000 if `NEXT_PUBLIC_API_URL` is not set. Always ensure `frontend/.env.local` has `NEXT_PUBLIC_API_URL=http://localhost:8000/api`.

### Docker
```bash
make setup          # First time (build + start + seed)
make up / make down # Start/stop containers
make fresh          # Reset database (migrate:fresh --seed)
make shell          # Shell into app container
make test           # Run tests in Docker
make lite-up        # SQLite version (lighter)
make clean          # Remove all containers and volumes
```

### Code Quality
```bash
./vendor/bin/pint             # Laravel formatting (PSR-12)
cd frontend && npm run lint   # Frontend linting (ESLint)
```

## Testing Commands

### Backend
```bash
php artisan test                                           # All tests (791 tests)
composer test                                              # Clears config cache first, then runs tests
php artisan test --coverage                                # With coverage report
php artisan test --filter=TestClassName                    # Single test file
php artisan test --filter=TestClassName::test_method_name  # Single test method
```

Tests use **SQLite in-memory** (configured in `phpunit.xml`) — no MySQL needed to run tests locally.

CI enforces **100% code coverage** (`--min=100`) and runs `./vendor/bin/pint --test` for style checks.

### Frontend
```bash
cd frontend
npm test                      # Unit tests (Jest, 321 tests)
npm run test:watch            # Watch mode
npm run test:coverage         # With coverage
npm run test:e2e              # E2E tests (Playwright)
npm run test:e2e:ui           # E2E with Playwright UI
npm run test:e2e:headed       # E2E in headed browser
```

Test locations:
- Backend unit tests: `tests/Unit/`
- Backend feature tests: `tests/Feature/Api/`
- Frontend unit tests: `frontend/src/__tests__/`
- E2E tests: `frontend/e2e/`

## Architecture

### Backend Flow
```
routes/api.php → Controllers → Services → Models → Observers (side effects)
                     ↓                                    ↓
              Form Requests (validation)           Notifications
                     ↓
              Resources (API response transformation)
                     ↓
              ApiResponse helper (consistent JSON format)
```

### Controller Organization
- `app/Http/Controllers/Api/` — Patient-facing controllers (AppointmentController, AuthController, etc.)
- `app/Http/Controllers/Api/Admin/` — Admin-facing controllers (aliased to avoid name collisions, e.g., `AdminAppointmentController`)
- `app/Http/Controllers/Web/` — Cookie-based auth controller for SPA login/logout

### Form Requests Organization
- `app/Http/Requests/Auth/` — Auth validation (LoginRequest, RegisterRequest, etc.)
- `app/Http/Requests/Admin/` — Admin validation (StoreMedicalRecordRequest, StorePrescriptionRequest, etc.)
- `app/Http/Requests/` — Shared/patient validation (BookAppointmentRequest, etc.)

### Route Groups (routes/api.php)
- **Public**: `/api/auth/*`, `/api/slots/*`, `/api/health`
- **Patient (auth:sanctum)**: `/api/appointments/*`, `/api/patient/*`, `/api/medical-records/*`, `/api/prescriptions/*`, `/api/notifications/*`
- **Admin (auth:sanctum + admin)**: `/api/admin/*` (dashboard, reports, settings, schedules, vacations, appointments, patients, medical-records, prescriptions, payments, attachments)

### Rate Limiting (configured in AppServiceProvider)
| Limiter | Requests/min | Applied to |
|---------|-------------|------------|
| `auth` | 5 | Auth endpoints |
| `api` | 60 | All authenticated routes |
| `slots` | 20 | Slot availability checks |
| `booking` | 3 | Appointment booking |

### Middleware Stack (bootstrap/app.php)
Global API middleware (order matters):
- **Prepend**: `AuthenticateFromCookie` — extracts token from HttpOnly cookie for SPA auth
- **Append**: `AddRequestId`, `SecurityHeaders`

Aliases:
- `admin` → `AdminMiddleware` (allows both admin and secretary roles)
- `secretary` → `SecretaryMiddleware`
- `cache.api` → `CacheApiResponse`

Note: `SetLocale` middleware is appended to API middleware stack — locale is set per-request via `Accept-Language` header or `?lang=` query param.

### Core Services (app/Services/)
| Service | Responsibility |
|---------|----------------|
| SlotGeneratorService | Generates slots from schedules, handles vacation blocking |
| AppointmentService | Booking logic, status transitions, conflict detection |
| PaymentService | Payment processing, revenue calculations |
| NotificationService | Database notifications with optional SMS |
| PrescriptionPdfService | PDF generation with DomPDF |
| DashboardService | Statistics and charts |
| ReportService | Appointment, revenue, patient reports |
| CacheInvalidationService | Tag-based cache invalidation for API responses |
| SmsService | SMS sending abstraction (OTP, notifications) |
| PatientStatisticsService | Patient-specific statistics aggregation |
| LocalizationService | Multi-language support (ar/en) |

### Authorization Policies (app/Policies/)
Policies enforce ownership-based access: `AppointmentPolicy`, `MedicalRecordPolicy`, `PatientProfilePolicy`, `PrescriptionPolicy`, `PaymentPolicy`. Patients can only access their own records; admins/secretaries can access all.

### Model Observers (app/Observers/)
Registered in `AppServiceProvider::boot()`:
- `AppointmentObserver`, `PaymentObserver`, `UserObserver`, `ScheduleObserver`, `VacationObserver`, `MedicalRecordObserver`

### ApiResponse Helper (app/Http/Helpers/ApiResponse.php)
All controllers use `ApiResponse` for consistent JSON:
- `ApiResponse::success($data, $message, $code)`, `::created()`, `::error()`, `::paginated($paginator, $resourceClass)`
- Shortcuts: `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`, `tooManyRequests()`, `serverError()`
- Paginated responses include `meta` (current_page, last_page, per_page, total) and `links` (first, last, prev, next)

### Custom Exceptions (app/Exceptions/)
Handled globally in `bootstrap/app.php`:
- `BusinessLogicException` — domain logic errors with `error_code` and `context`
- `PaymentException` — payment-specific errors with `appointment_id` and `amount`
- `SlotNotAvailableException` — thrown when booking an unavailable slot

### Key Model Relationships
```
User (admin/secretary/patient)
  └── PatientProfile
  └── Appointment[]
        └── MedicalRecord
              └── Prescription → PrescriptionItem[]
              └── Attachment[] (polymorphic)
        └── Payment

Schedule (day_of_week 0-6, break times)
Vacation (single dates that block booking)
ClinicSetting (singleton)
```

### Enums (app/Enums/)
UserRole, AppointmentStatus, CancelledBy, PaymentMethod, PaymentStatus, BloodType, Gender, DayOfWeek

### Frontend Architecture (frontend/src/)
```
app/
  (auth)/         # Login, register, forgot-password, verify-otp, reset-password
  (patient)/      # Patient dashboard, appointments, book, medical-records, prescriptions, profile, notifications
  (admin)/admin/  # Admin dashboard, patients, appointments, settings, schedules, vacations, medical-records, prescriptions, payments, reports
components/
  ui/             # Radix UI primitives (button, input, dialog, etc.)
  layouts/        # AuthLayout, PatientLayout, AdminLayout
  shared/         # LanguageSwitcher, LoadingSpinner, Avatar, VirtualizedList
  providers/      # React Query, Zustand providers
lib/
  api/            # API client (client.ts) + domain modules (auth.ts, patient.ts, admin.ts, appointments.ts)
  stores/         # Zustand stores (auth.ts — persisted to localStorage)
  validations/    # Zod schemas (auth.ts, api-responses.ts)
  utils/          # Utilities (utils.ts, sanitize.ts)
hooks/            # Custom hooks (useDebounce.ts)
i18n/             # next-intl config
types/            # TypeScript types
__tests__/        # Jest tests with MSW mocks
```

### Frontend Stack
- **Routing**: Next.js 16 App Router with route groups
- **State**: Zustand (auth, persisted to localStorage) + React Query (server state, 5-min stale time)
- **Forms**: React Hook Form + Zod validation
- **UI**: Radix UI primitives + Tailwind CSS
- **i18n**: next-intl (Arabic default, English fallback)
- **Testing**: Jest + Testing Library + MSW (mocks) + Playwright (E2E)

### Frontend API Client (frontend/src/lib/api/client.ts)
- Axios instance with `withCredentials: true` (cookie auth)
- Auto-retry: up to 3 retries with exponential backoff on idempotent methods (GET/PUT/DELETE) for status codes 408, 429, 500, 502, 503, 504
- On 401: attempts token refresh via `/auth/refresh`, queues concurrent requests during refresh, redirects to `/login` on failure
- Custom `ApiError` class with `status`, `code`, and `details` (validation errors)
- Locale sent via `Accept-Language` header from `localStorage`

## Development Workflow

### After Each Feature
1. Run `php artisan test` (and `cd frontend && npm test` for frontend changes)
2. Update `lang/ar.json` and `lang/en.json` if new strings added
3. Update PROGRESS.md

### Commit Convention
```
feat(scope): description
fix(scope): description
test(scope): description
docs(scope): description
refactor(scope): description
```

### Key Paths
| Task | Path |
|------|------|
| Add API endpoint | `routes/api.php` → `app/Http/Controllers/` → `app/Http/Requests/` |
| Add service | `app/Services/` (bind in `app/Providers/AppServiceProvider.php`) |
| Add translation | `lang/ar.json`, `lang/en.json` |
| Add frontend API | `frontend/src/lib/api/` |
| Add admin page | `frontend/src/app/(admin)/admin/` |
| Add patient page | `frontend/src/app/(patient)/` |

## API Details

> **Full API Documentation**: See [API.md](./API.md) for comprehensive documentation of all 88 endpoints.

### Response Format
```json
{"success": true|false, "message": "...", "data": {...}}
```

### Authentication
- **SPA (frontend)**: HttpOnly cookies via `AuthenticateFromCookie` middleware. Frontend uses `withCredentials: true` on axios — no Bearer token in JS.
- **External API**: `Authorization: Bearer {token}` header (Laravel Sanctum)
- On 401, frontend auto-redirects to `/login`

### Localization
Header: `Accept-Language: ar|en` or query: `?lang=ar`
Default: Arabic (ar), Fallback: English (en)

### Default Admin (after seeding)
Phone: 01000000000, Password: admin123

## Environment
- PHP 8.2+, MySQL 8.0+ or SQLite, Node.js 18+
- Queue driver: database (for notifications)
- Backend: localhost:8000, Frontend: localhost:3000
- Tests: SQLite in-memory (no external DB required)

## Status Flows

**Appointment**: `pending` → `confirmed` → `completed` (can also → `cancelled` or `no_show`)

**Payment**: `pending` → `paid` → `refunded`

## CI/CD

Two GitHub Actions workflows:
- **ci.yml** (push/PR to main/develop): Backend tests with MySQL + 100% coverage, frontend lint + tests + build, E2E tests with Playwright
- **pr-check.yml** (PRs): Commit message validation, merge conflict check, lint (Pint + ESLint), quick test suite (SQLite)
