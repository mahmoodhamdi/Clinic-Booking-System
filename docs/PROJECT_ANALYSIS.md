# Project Analysis — Clinic Booking System

## Overview

A Clinic Booking System for a single doctor's private clinic. Three user types: Admin (Doctor), Secretary, and Patients. Built with **Laravel 12** backend + **Next.js 16** frontend.

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | 12.0 |
| Frontend Framework | Next.js | 16.1.0 |
| Language (Backend) | PHP | 8.2+ (running 8.3.30) |
| Language (Frontend) | TypeScript | 5.x (strict mode) |
| Database | SQLite (dev) / MySQL 8.0 (prod) | - |
| Auth | Laravel Sanctum | 4.2 |
| State Management | Zustand (client) + React Query (server) | 5.x / 5.90 |
| UI Framework | Radix UI + Tailwind CSS | 4.x |
| Forms | React Hook Form + Zod | - |
| i18n | next-intl | 4.6 |
| PDF | DomPDF + mPDF | 3.1 / 8.2 |
| Testing (Backend) | PHPUnit | 11.5 |
| Testing (Frontend) | Jest + Playwright | - |
| API Mocking | MSW (Mock Service Worker) | - |

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

### Component Counts

| Component | Count |
|-----------|-------|
| API Endpoints | 88 |
| Controllers | 19 |
| Models | 11 |
| Services | 11 |
| Middleware | 7 |
| Form Requests | 26 |
| Resources | 18 |
| Policies | 5 |
| Observers | 6 |
| Enums | 8 |
| Exceptions | 3 |
| Backend Tests | 870 |
| Frontend Tests | 500 |
| Frontend Pages | 22 |
| UI Components | 22 |

## Database Schema

### Users
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | auto-increment |
| name | string | required |
| phone | string | unique, required |
| email | string | nullable, unique |
| role | enum | admin, secretary, patient |
| password | string | hashed (bcrypt) |
| avatar | string | nullable |
| is_active | boolean | default true |
| phone_verified_at | timestamp | nullable |
| date_of_birth | date | nullable |
| gender | enum | male, female, nullable |
| address | text | nullable |

### Appointments
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| patient_id | FK → users | |
| appointment_date | date | |
| appointment_time | time | |
| end_time | time | |
| status | enum | pending, confirmed, completed, cancelled, no_show |
| notes | text | nullable |
| admin_notes | text | nullable |
| cancellation_reason | text | nullable |
| cancelled_by | enum | patient, admin, nullable |
| cancelled_at | timestamp | nullable |
| confirmed_at | timestamp | nullable |
| completed_at | timestamp | nullable |

### Medical Records
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| patient_id | FK → users | |
| appointment_id | FK → appointments | nullable |
| diagnosis | text | required |
| notes | text | nullable |
| treatment_plan | text | nullable |
| blood_pressure_systolic | integer | nullable |
| blood_pressure_diastolic | integer | nullable |
| heart_rate | integer | nullable |
| temperature | decimal | nullable |
| weight | decimal | nullable |
| height | decimal | nullable |
| follow_up_date | date | nullable |
| follow_up_notes | text | nullable |

### Prescriptions
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| medical_record_id | FK → medical_records | |
| prescription_number | string | unique, auto-generated (RX-YYYY-NNNN) |
| status | string | active, dispensed |
| dispensed_at | timestamp | nullable |

### Prescription Items
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| prescription_id | FK → prescriptions | |
| medication_name | string | required |
| dosage | string | required |
| frequency | string | required |
| duration | string | required |
| instructions | text | nullable |

### Payments
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| appointment_id | FK → appointments | nullable |
| patient_id | FK → users | |
| amount | decimal(10,2) | |
| discount | decimal(10,2) | default 0 |
| payment_method | enum | cash, card, bank_transfer, insurance |
| status | enum | pending, paid, refunded, overdue |
| notes | text | nullable |
| paid_at | timestamp | nullable |
| refunded_at | timestamp | nullable |

### Schedules
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| day_of_week | integer | 0-6 (Sunday-Saturday) |
| start_time | time | |
| end_time | time | |
| break_start | time | nullable |
| break_end | time | nullable |
| is_active | boolean | |

### Vacations
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| date / start_date | date | |
| end_date | date | |
| reason | text | nullable |
| title | string | nullable |

### Clinic Settings (Singleton)
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| clinic_name | string | |
| clinic_phone | string | nullable |
| clinic_email | string | nullable |
| clinic_address | text | nullable |
| logo_path | string | nullable |
| slot_duration | integer | minutes |
| max_patients_per_slot | integer | |
| advance_booking_days | integer | |
| cancellation_hours | integer | |
| consultation_fee | decimal | |

### Attachments (Polymorphic)
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| attachable_id | bigint | polymorphic |
| attachable_type | string | polymorphic (MedicalRecord, Prescription) |
| user_id | FK → users | uploader |
| file_path | string | |
| original_name | string | |
| file_size | integer | bytes |
| file_type | string | image, document, other |

## Security Architecture

- **Authentication**: Laravel Sanctum with HttpOnly cookies (SPA), Bearer tokens (API)
- **Password Hashing**: bcrypt with 12 rounds
- **Session**: Encrypted, database-backed
- **CSRF**: Sanctum built-in
- **Rate Limiting**: 5/min auth, 3/min booking, 60/min API, 30/min slots
- **Security Headers**: HSTS, X-Content-Type-Options, X-Frame-Options, CSP, Referrer-Policy, Permissions-Policy
- **Input Validation**: Laravel Form Requests (server) + Zod schemas (client)
- **XSS Protection**: DOMPurify (frontend), CSP headers, React built-in
- **SQL Injection**: Eloquent ORM parameterized queries throughout

## Environment Variables

| Variable | Description | Required | Default |
|----------|-------------|----------|---------|
| APP_KEY | Application encryption key | Yes | - |
| DB_CONNECTION | Database driver | Yes | sqlite |
| DB_DATABASE | Database path/name | Yes | database.sqlite |
| SANCTUM_TOKEN_EXPIRATION | Token TTL in minutes | No | 240 |
| SESSION_ENCRYPT | Encrypt session data | No | true |
| BCRYPT_ROUNDS | Password hash rounds | No | 12 |
| LOCALE | Default locale | No | ar |
| FALLBACK_LOCALE | Fallback locale | No | en |
| QUEUE_CONNECTION | Queue driver | No | database |
| CACHE_STORE | Cache backend | No | database |

## Code Quality Assessment

| Dimension | Score | Notes |
|-----------|-------|-------|
| Architecture | 9/10 | Clean layered architecture, proper separation of concerns |
| Type Safety | 9/10 | TypeScript strict mode, no `any` types, typed PHP |
| Test Coverage | 10/10 | 870 backend + 500 frontend tests, 100% coverage enforced |
| Security | 8/10 | Strong baseline; minor issues found and fixed |
| Performance | 8/10 | Caching, eager loading, debouncing; SQLite-specific queries |
| Accessibility | 7/10 | Improved after fixes; still needs more aria attributes |
| i18n | 8/10 | Full Arabic/English support; Zod validation strings still hardcoded EN |
| Error Handling | 9/10 | Domain exceptions, consistent API response format |
| Code Style | 9/10 | PSR-12 (Laravel Pint), ESLint, consistent naming |
| Documentation | 8/10 | CLAUDE.md, API.md, inline code comments |
| **Overall** | **85/100** | Production-ready with minor improvements possible |
