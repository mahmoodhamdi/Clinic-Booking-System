# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Clinic Booking System for a single doctor's private clinic built with **Laravel 11**, **Blade Templates**, and **REST APIs**. The system serves three user types: Admin (Doctor), Secretary, and Patients.

## Build & Development Commands

```bash
# Install dependencies
composer install
npm install

# Run development server
php artisan serve

# Database operations
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed

# Clear all caches
php artisan optimize:clear

# Code formatting
./vendor/bin/pint
```

## Testing Commands

```bash
# Run all tests
php artisan test

# Run tests with coverage (100% required)
php artisan test --coverage --min=100

# Run specific test file
php artisan test --filter=TestClassName

# Run single test method
php artisan test --filter=TestClassName::test_method_name

# Generate HTML coverage report
php artisan test --coverage-html=coverage
# Or using PHPUnit directly
./vendor/bin/phpunit --coverage-html coverage-report
```

## Architecture

### Directory Structure
- `app/Http/Controllers/Api/` - REST API controllers for mobile/external clients
- `app/Http/Controllers/Admin/` - Admin dashboard controllers (Doctor/Secretary)
- `app/Http/Controllers/Web/` - Public-facing web controllers (Booking, Patient Portal)
- `app/Services/` - Business logic layer (AppointmentService, SlotGeneratorService, PaymentService, NotificationService, SmsService)
- `app/Enums/` - PHP enums for constants (UserRole, AppointmentStatus, AppointmentType, PaymentMethod, PaymentStatus, Gender)
- `app/Http/Resources/` - API resource transformers
- `app/Http/Requests/` - Form request validation classes

### Route Files
- `routes/api.php` - REST API routes
- `routes/web.php` - Public web routes
- `routes/admin.php` - Admin panel routes

### Blade Template Organization
- `resources/views/layouts/` - Base layouts (app, admin, guest, print)
- `resources/views/components/` - Reusable UI components
- `resources/views/auth/` - Authentication pages
- `resources/views/booking/` - Multi-step booking flow
- `resources/views/patient/` - Patient portal views
- `resources/views/admin/` - Admin dashboard views

### Core Services
- **SlotGeneratorService** - Generates available appointment slots based on schedules and vacations
- **AppointmentService** - Handles booking logic, status transitions, conflicts
- **PaymentService** - Payment processing and revenue calculations
- **NotificationService** - Manages notifications (database + SMS)
- **SmsService** - SMS delivery (supports log driver for development)

### Key Models & Relationships
- **User** - Has roles (admin/secretary/patient), has PatientProfile, has many Appointments
- **Appointment** - Belongs to Patient (User), has one MedicalRecord, has one Payment
- **MedicalRecord** - Belongs to Appointment, has one Prescription, has many Attachments
- **Prescription** - Has many PrescriptionItems
- **Schedule** - Weekly working hours (day_of_week 0-6), break times
- **Vacation** - Single date holidays that block booking

## Development Workflow

### TDD Requirements (Mandatory)
1. Write unit tests for models, services, enums
2. Write feature/integration tests for API endpoints and controllers
3. Achieve 100% code coverage before committing
4. All tests must pass before pushing

### After Each Feature
1. Run `php artisan test --coverage --min=100`
2. Update PROGRESS.md with completed tasks
3. Commit with conventional message: `feat(module): description`
4. Push to GitHub

### Commit Convention
```
feat(auth): implement user registration
fix(booking): fix slot calculation bug
test(appointments): add integration tests
docs(api): update API documentation
refactor(services): improve code structure
```

## API Response Format

All API responses follow this structure:
```json
{
  "success": true|false,
  "message": "Description",
  "data": { ... }
}
```

## Key Packages
- **laravel/sanctum** - API authentication
- **spatie/laravel-permission** - Roles & permissions
- **barryvdh/laravel-dompdf** - PDF prescription generation
- **intervention/image** - Image processing
- **propaganistas/laravel-phone** - Phone validation

## Environment
- PHP 8.2+
- MySQL 8.0+
- Queue driver: database (for notifications)
- SMS driver: log (development) or actual provider (production)
- Default locale: ar (Arabic) with en fallback
