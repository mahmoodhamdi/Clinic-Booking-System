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
# Backend (runs API + queue + logs + vite concurrently)
composer dev

# Frontend (in separate terminal)
cd frontend && npm run dev
```

Backend runs on localhost:8000, Frontend on localhost:3000.

### Docker
```bash
docker-start.bat / ./docker-start.sh    # Quick start (Windows/Linux)
make setup                               # First time (build + start + seed)
make up / make down                      # Start/stop containers
make fresh                               # Reset database (migrate:fresh --seed)
make shell                               # Shell into app container
make test                                # Run tests in Docker
make lite-up / make lite-down            # SQLite version (lighter)
make with-tools                          # Includes phpMyAdmin
make logs                                # View container logs
make status                              # Show container status
make clean                               # Remove all containers and volumes
```

### Code Quality
```bash
./vendor/bin/pint             # Laravel formatting (PSR-12)
cd frontend && npm run lint   # Frontend linting
```

## Testing Commands

### Backend
```bash
php artisan test                                           # All tests (791 tests)
composer test                                              # Clears config first
php artisan test --coverage                                # With coverage report
php artisan test --filter=TestClassName                    # Single test file
php artisan test --filter=TestClassName::test_method_name  # Single test method
```

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
- `app/Http/Controllers/Api/` - Patient-facing controllers (AppointmentController, AuthController, etc.)
- `app/Http/Controllers/Api/Admin/` - Admin-facing controllers (aliased to avoid name collisions, e.g., `AdminAppointmentController`)
- `app/Http/Controllers/Web/` - Cookie-based auth controller for SPA login/logout

### Route Groups (routes/api.php)
- **Public**: `/api/auth/*`, `/api/slots/*`, `/api/health` (health check)
- **Patient (auth:sanctum)**: `/api/appointments/*`, `/api/patient/*`, `/api/medical-records/*`, `/api/prescriptions/*`, `/api/notifications/*`
- **Admin (auth:sanctum + admin)**: `/api/admin/*` (dashboard, reports, settings, schedules, vacations, appointments, patients, medical-records, prescriptions, payments)

### Rate Limiting Tiers
- `throttle:auth` - Strict limits on authentication endpoints
- `throttle:api` - Standard API rate limiting for authenticated routes
- `throttle:slots` - Rate limiting for slot availability checks
- `throttle:booking` - Rate limiting for appointment booking

### Custom Middleware (app/Http/Middleware/)
- `AdminMiddleware` - Restricts routes to admin role (used on /api/admin/* routes, allows both admin and secretary)
- `SecretaryMiddleware` - Restricts routes to secretary role only
- `SetLocale` - Sets locale from Accept-Language header or query param
- `SecurityHeaders` - Adds security headers to responses (appended to all API responses)
- `AddRequestId` - Adds unique request ID to all API responses (appended to all API responses)
- `CacheApiResponse` - Caches API responses (alias: `cache.api`)
- `AuthenticateFromCookie` - Cookie-based auth for SPA (prepended to all API requests)

### Core Services (app/Services/)
| Service | Responsibility |
|---------|----------------|
| SlotGeneratorService | Generates slots from schedules, handles vacation blocking |
| AppointmentService | Booking logic, status transitions (pending→confirmed→completed), conflict detection |
| PaymentService | Payment processing, revenue calculations |
| NotificationService | Database notifications with optional SMS |
| PrescriptionPdfService | PDF generation with DomPDF |
| DashboardService | Statistics and charts |
| ReportService | Appointment, revenue, patient reports |
| LocalizationService | Multi-language support (ar/en) |

### Model Observers (app/Observers/)
Observers auto-trigger side effects on model lifecycle events. Registered in `AppServiceProvider::boot()`.
- `AppointmentObserver` - Handles appointment creation/update side effects
- `PaymentObserver` - Payment event handling
- `UserObserver` - User lifecycle events
- `ScheduleObserver` - Schedule change side effects
- `VacationObserver` - Vacation change handling
- `MedicalRecordObserver` - Medical record lifecycle events

### ApiResponse Helper (app/Http/Helpers/ApiResponse.php)
All controllers use `ApiResponse` for consistent JSON responses:
- `ApiResponse::success($data, $message, $code)` - Standard success
- `ApiResponse::created($data, $message)` - 201 response
- `ApiResponse::error($message, $code, $errors)` - Error response
- `ApiResponse::paginated($paginator, $resourceClass)` - Paginated response with `meta` (current_page, last_page, per_page, total) and `links` (first, last, prev, next)
- Shortcut methods: `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`, `tooManyRequests()`, `serverError()`

### Custom Exceptions (app/Exceptions/)
Handled globally in `bootstrap/app.php`:
- `BusinessLogicException` - Domain logic errors with `error_code` and `context`
- `PaymentException` - Payment-specific errors with `appointment_id` and `amount`
- `SlotNotAvailableException` - Thrown when booking an unavailable slot

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

### Authorization Policies (app/Policies/)
Registered via `Gate::policy()` in `AppServiceProvider::boot()`:
- `AppointmentPolicy`, `MedicalRecordPolicy`, `PrescriptionPolicy`, `PaymentPolicy`, `PatientProfilePolicy`

### Enums (app/Enums/)
- UserRole, AppointmentStatus, CancelledBy
- PaymentMethod, PaymentStatus
- BloodType, Gender, DayOfWeek

### Frontend Architecture (frontend/src/)
```
app/                          # Next.js 16 App Router
  (auth)/                     # Auth pages (login, register, forgot-password, verify-otp, reset-password)
  (patient)/                  # Patient pages (dashboard, appointments, book, medical-records, etc.)
  (admin)/admin/              # Admin pages (dashboard, patients, appointments, settings, etc.)
components/
  ui/                         # Radix UI primitives (button, input, dialog, etc.)
  layouts/                    # AuthLayout, PatientLayout, AdminLayout
  shared/                     # LanguageSwitcher, LoadingSpinner, Avatar, VirtualizedList
  providers/                  # React Query, Zustand providers
lib/
  api/                        # API client (client.ts, auth.ts, patient.ts, admin.ts, appointments.ts)
  stores/                     # Zustand stores (auth.ts)
  validations/                # Zod schemas (auth.ts, api-responses.ts)
  utils/                      # Utilities (utils.ts, sanitize.ts)
hooks/                        # Custom hooks (useDebounce.ts)
i18n/                         # next-intl config
types/                        # TypeScript types
__tests__/                    # Jest tests with MSW mocks
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
- Custom `ApiError` class with `status`, `code`, and `details` (validation errors)
- Locale sent via `Accept-Language` header from `localStorage`

## Development Workflow

### Testing Requirements
- Write unit tests for models, services, enums in `tests/Unit/`
- Write feature tests for API endpoints in `tests/Feature/Api/`
- All tests must pass before push
- Run `php artisan test --coverage` to verify coverage

### After Each Feature
1. Run `php artisan test`
2. Update `lang/ar.json` and `lang/en.json` if new strings added
3. Update PROGRESS.md

### Commit Convention
```
feat(scope): description   # New feature
fix(scope): description    # Bug fix
test(scope): description   # Tests
docs(scope): description   # Documentation
refactor(scope): description  # Code refactoring
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

### OTP Security
- Brute force protection: 5 attempts max, 30-minute lockout
- OTP expires after 10 minutes

## Environment
- PHP 8.2+, MySQL 8.0+ or SQLite, Node.js 18+
- Queue driver: database (for notifications)
- Backend: localhost:8000, Frontend: localhost:3000

## Status Flows

**Appointment**: `pending` → `confirmed` → `completed` (can also → `cancelled` or `no_show`)

**Payment**: `pending` → `paid` → `refunded`
