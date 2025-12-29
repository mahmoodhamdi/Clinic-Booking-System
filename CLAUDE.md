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
# Backend (recommended - runs API + queue + logs + vite concurrently)
composer dev

# Frontend
cd frontend && npm run dev
```

### Docker Quick Start
```bash
docker-start.bat              # Windows
./docker-start.sh             # Linux/Mac
make setup                    # First time (build + start + seed)
```

### Code Quality
```bash
./vendor/bin/pint             # Laravel formatting (PSR-12)
cd frontend && npm run lint   # Frontend linting
```

## Testing Commands

### Backend
```bash
php artisan test                                           # All tests (544 tests)
composer test                                              # Clears config first
php artisan test --coverage --min=100                      # With coverage requirement
php artisan test --filter=TestClassName                    # Single test file
php artisan test --filter=TestClassName::test_method_name  # Single test method
```

### Frontend
```bash
cd frontend
npm test                      # Unit tests (Jest)
npm run test:watch            # Watch mode
npm run test:coverage         # With coverage
npm run test:e2e              # E2E tests (Playwright)
```

## Architecture

### Backend Flow
```
routes/api.php → Controllers → Services → Models
                     ↓
              Form Requests (validation)
                     ↓
              Resources (API response transformation)
```

### Route Groups (routes/api.php)
- **Public**: `/api/auth/*`, `/api/slots/*`
- **Patient (auth:sanctum)**: `/api/appointments/*`, `/api/patient/*`, `/api/medical-records/*`, `/api/prescriptions/*`, `/api/notifications/*`
- **Admin (auth:sanctum + admin)**: `/api/admin/*` (dashboard, reports, settings, schedules, vacations, appointments, patients, medical-records, prescriptions, payments)

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
- **State**: Zustand (auth) + React Query (server state)
- **Forms**: React Hook Form + Zod validation
- **UI**: Radix UI primitives + Tailwind CSS
- **i18n**: next-intl (Arabic default, English fallback)
- **Testing**: Jest + Testing Library + MSW (mocks) + Playwright (E2E)

## Development Workflow

### TDD Requirements
1. Write unit tests for models, services, enums (tests/Unit/)
2. Write feature tests for API endpoints (tests/Feature/Api/)
3. **100% code coverage required** before commit
4. All tests must pass before push

### After Each Feature
1. Run `php artisan test --coverage --min=100`
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

## API Details

### Response Format
```json
{"success": true|false, "message": "...", "data": {...}}
```

### Authentication
Header: `Authorization: Bearer {token}` (Laravel Sanctum)

### Localization
Header: `Accept-Language: ar|en` or query: `?lang=ar`
Default: Arabic (ar), Fallback: English (en)

### Default Admin (after seeding)
Phone: 01000000000, Password: admin123

## Environment
- PHP 8.2+, MySQL 8.0+ or SQLite, Node.js 18+
- Queue driver: database (for notifications)
- Backend: localhost:8000, Frontend: localhost:3000
