# Clinic Booking System

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/mahmoodhamdi/Clinic-Booking-System/releases/tag/v1.0.0)
[![Backend Tests](https://img.shields.io/badge/backend%20tests-791%20passing-brightgreen.svg)]()
[![Frontend Tests](https://img.shields.io/badge/frontend%20tests-321%20passing-brightgreen.svg)]()
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A comprehensive Clinic Booking System for private medical clinics built with **Laravel 12** backend and **Next.js 16** frontend. Supports three user types: Admin (Doctor), Secretary, and Patients.

## Screenshots

<details>
<summary>View Screenshots</summary>

### Patient Portal
- Dashboard with upcoming appointments
- Book appointments with available slots
- View medical records and prescriptions

### Admin Dashboard
- Real-time statistics and charts
- Appointment management
- Patient records management

</details>

## Features

### Authentication & Security
- Phone-based authentication with OTP verification
- Role-based access control (Admin, Secretary, Patient)
- Brute force protection for OTP (5 attempts, 30-minute lockout)
- Rate limiting on all endpoints
- Comprehensive authorization policies
- Secure password reset flow

### Patient Portal
- Interactive appointment booking with calendar
- Real-time slot availability
- Appointment history and status tracking
- Medical records access
- Prescription viewing and PDF download
- Profile management with medical info
- Notification center

### Admin Dashboard
- Real-time statistics (patients, appointments, revenue)
- Interactive charts and trends
- Today's appointments overview
- Recent activity feed
- Auto-refresh every 30 seconds

### Appointment Management
- View all appointments with filtering
- Status management (pending → confirmed → completed)
- Cancel and no-show handling
- Notes and updates

### Patient Management
- Complete patient profiles
- Medical history tracking
- Blood type, allergies, chronic diseases
- Emergency contact information
- Insurance details
- Appointment statistics per patient

### Medical Records
- Detailed examination documentation
- Vital signs tracking (BP, heart rate, temperature, weight, height)
- Diagnosis and treatment plans
- Follow-up scheduling
- File attachments (images, PDFs, documents)

### Prescriptions
- Digital prescription creation
- Medication details with dosage and instructions
- PDF generation and download
- Dispensing status tracking
- Expiry date management

### Payments
- Multiple payment methods (Cash, Card, Insurance)
- Discount management
- Payment status tracking
- Revenue reporting

### Reports
- Appointments report with filtering
- Revenue report by date range
- Patient statistics report
- PDF export functionality

### Schedule Management
- Weekly schedule configuration
- Break time settings
- Vacation/holiday management
- Slot duration customization

### Notifications
- In-app notification system
- Appointment reminders
- Status change alerts
- Mark as read functionality

### Localization
- Multi-language support (Arabic, English)
- RTL layout support
- Seamless language switching

### Performance Optimizations
- React Query caching (5-min stale time)
- Lazy loaded components
- Image optimization (AVIF, WebP)
- Font optimization with next/font

## Tech Stack

### Backend
- **Framework**: Laravel 12
- **PHP Version**: 8.2+
- **Authentication**: Laravel Sanctum
- **Database**: MySQL / SQLite
- **PDF Generation**: DomPDF
- **Queue**: Database driver
- **Testing**: PHPUnit (791 tests)

### Frontend
- **Framework**: Next.js 16 (App Router)
- **Language**: TypeScript
- **State Management**: Zustand + React Query
- **Forms**: React Hook Form + Zod
- **UI Components**: Radix UI + Tailwind CSS
- **Internationalization**: next-intl
- **Testing**: Jest (321 tests) + Playwright (E2E)

### DevOps
- **Containerization**: Docker + Docker Compose
- **CI/CD Ready**: GitHub Actions compatible

## Quick Start

### Using Docker (Recommended)

```bash
# Clone repository
git clone https://github.com/mahmoodhamdi/Clinic-Booking-System.git
cd Clinic-Booking-System

# Windows
docker-start.bat

# Linux/Mac
chmod +x docker-start.sh
./docker-start.sh
```

Access:
- **Backend API**: http://localhost:8000
- **Frontend**: http://localhost:3000

### Manual Installation

#### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL or SQLite

#### Backend Setup

```bash
# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Start development server
composer dev
```

#### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

Access:
- **Backend API**: http://localhost:8000
- **Frontend**: http://localhost:3000

## Default Credentials

After running seeders:

| Role | Phone | Password |
|------|-------|----------|
| Admin | 01000000000 | admin123 |

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
```
Authorization: Bearer {token}
```

### Localization
```
Accept-Language: ar|en
```

### Main Endpoints

| Category | Endpoint | Description |
|----------|----------|-------------|
| **Auth** | POST /auth/register | Register patient |
| | POST /auth/login | Login |
| | POST /auth/forgot-password | Request OTP |
| | POST /auth/verify-otp | Verify OTP |
| | POST /auth/reset-password | Reset password |
| **Slots** | GET /slots/dates | Available dates |
| | GET /slots/{date} | Slots for date |
| **Appointments** | GET /appointments | My appointments |
| | POST /appointments | Book appointment |
| | POST /appointments/{id}/cancel | Cancel |
| **Medical Records** | GET /medical-records | My records |
| | GET /medical-records/{id} | Record details |
| **Prescriptions** | GET /prescriptions | My prescriptions |
| | GET /prescriptions/{id}/pdf | Download PDF |

### Admin Endpoints (requires admin role)

| Category | Endpoint | Description |
|----------|----------|-------------|
| **Dashboard** | GET /admin/dashboard/stats | Statistics |
| | GET /admin/dashboard/today | Today's appointments |
| **Appointments** | GET /admin/appointments | All appointments |
| | POST /admin/appointments/{id}/confirm | Confirm |
| | POST /admin/appointments/{id}/complete | Complete |
| **Patients** | GET /admin/patients | All patients |
| **Medical Records** | POST /admin/medical-records | Create record |
| **Prescriptions** | POST /admin/prescriptions | Create prescription |
| **Payments** | GET /admin/payments | All payments |
| **Reports** | GET /admin/reports/appointments | Appointments report |
| | GET /admin/reports/revenue | Revenue report |
| **Settings** | GET /admin/settings | Clinic settings |
| | PUT /admin/settings | Update settings |
| **Schedules** | GET /admin/schedules | Weekly schedule |
| **Vacations** | GET /admin/vacations | Vacation days |

## Testing

### Backend Tests
```bash
# Run all tests
php artisan test

# With coverage
php artisan test --coverage

# Specific test
php artisan test --filter=AppointmentTest
```

### Frontend Tests
```bash
cd frontend

# Unit tests
npm test

# Watch mode
npm run test:watch

# E2E tests
npm run test:e2e
```

### Test Coverage

| Component | Tests | Assertions |
|-----------|-------|------------|
| Backend | 791 | 1,972 |
| Frontend | 321 | - |
| E2E | 4 specs | - |

## Project Structure

```
├── app/                    # Laravel application
│   ├── Enums/              # Enumerations
│   ├── Http/Controllers/   # API Controllers
│   ├── Models/             # Eloquent models
│   ├── Policies/           # Authorization policies
│   └── Services/           # Business logic
├── database/
│   ├── factories/          # Model factories
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── tests/                  # Backend tests
│   ├── Feature/            # Feature tests
│   └── Unit/               # Unit tests
├── frontend/               # Next.js application
│   ├── src/
│   │   ├── app/            # App router pages
│   │   ├── components/     # React components
│   │   ├── lib/            # Utilities & API
│   │   └── __tests__/      # Frontend tests
│   └── e2e/                # Playwright E2E tests
└── lang/                   # Translations
    ├── ar.json             # Arabic
    └── en.json             # English
```

## Configuration

### Clinic Settings
Configurable via Admin panel:
- Clinic name and contact info
- Slot duration (default: 30 minutes)
- Maximum patients per slot
- Advance booking days
- Cancellation hours limit

### Environment Variables

#### Backend (.env)
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_DATABASE=clinic_booking
QUEUE_CONNECTION=database
```

#### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for new features
4. Ensure all tests pass (`php artisan test && cd frontend && npm test`)
5. Commit changes (`git commit -m 'feat: add amazing feature'`)
6. Push to branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, email hmdy7486@gmail.com or open an issue on GitHub.

---

Built with [Laravel](https://laravel.com) and [Next.js](https://nextjs.org)
