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
cd frontend
npm install
cd ..
```

### Running Development Servers

#### Backend (Laravel)
```bash
composer dev                  # Full dev environment (API + queue + logs + vite) - RECOMMENDED
php artisan serve             # Just the API server (localhost:8000)
php artisan queue:listen      # Process background jobs (notifications)
```

#### Frontend (Next.js)
```bash
cd frontend
npm run dev                   # Development server (localhost:3000)
npm run build                 # Production build
npm start                     # Production server
```

#### Docker
```bash
# Quick start
docker-start.bat              # Windows - runs full stack
./docker-start.sh             # Linux/Mac - runs full stack

# Makefile commands (recommended)
make setup                    # First time setup (build + start + seed)
make up                       # Start containers
make down                     # Stop containers
make fresh                    # Fresh database + seed
make test                     # Run tests in container
make shell                    # Open shell in app container
make logs                     # View container logs
make with-tools               # Start with phpMyAdmin (localhost:8080)
```

### Code Quality
```bash
./vendor/bin/pint             # Laravel code formatting (PSR-12)
cd frontend && npm run lint   # Frontend linting
```

## Testing Commands

### Backend Tests
```bash
php artisan test                                    # Run all tests (544 tests)
composer test                                       # Alternative (clears config first)
php artisan test --coverage --min=100               # With 100% coverage requirement
php artisan test --filter=TestClassName             # Specific test file
php artisan test --filter=TestClassName::test_method_name  # Single test method
./vendor/bin/phpunit --coverage-html coverage-report      # Generate HTML report
```

### Frontend Tests
```bash
cd frontend
npm test                      # Unit tests (Jest)
npm run test:watch            # Watch mode
npm run test:coverage         # With coverage report
npm run test:e2e              # E2E tests (Playwright)
npm run test:e2e:ui           # E2E with UI
npm run test:e2e:headed       # E2E in headed mode
```

## Architecture

### Backend Layered Architecture
```
routes/api.php → Controllers → Services → Models
                     ↓
              Form Requests (validation)
                     ↓
              Resources (API response transformation)
```

### Route Groups (routes/api.php)
- **Public**: `/api/auth/*` (register, login, password reset), `/api/slots/*`
- **Patient (auth:sanctum)**: `/api/appointments/*`, `/api/patient/*`, `/api/medical-records/*`, `/api/prescriptions/*`, `/api/notifications/*`
- **Admin (auth:sanctum + admin)**: `/api/admin/*` (dashboard, reports, settings, schedules, vacations, appointments, patients, medical-records, prescriptions, payments)

### Core Services (app/Services/)
| Service | Responsibility |
|---------|----------------|
| SlotGeneratorService | Generates available slots from schedules, handles vacation blocking |
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

### Frontend Stack (frontend/)
- **Routing**: Next.js 16 App Router
- **State**: Zustand + React Query
- **Forms**: React Hook Form + Zod
- **UI**: Radix UI primitives + Tailwind CSS
- **i18n**: next-intl

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
