# Clinic Booking System

A comprehensive clinic management system for single-doctor private practices. Built with Laravel 12 and REST APIs.

## Features

### Patient Features
- Online appointment booking with slot selection
- View appointment history and upcoming appointments
- Access medical records and prescriptions
- Download prescription PDFs
- Receive notifications (appointment reminders, confirmations, cancellations)
- Profile management

### Admin Features
- Dashboard with real-time statistics and charts
- Appointment management (confirm, complete, cancel, no-show)
- Patient management with medical history
- Medical records creation and management
- Prescription generation with PDF export
- Payment tracking with multiple payment methods
- Revenue reports and PDF exports
- Schedule management (working hours per day)
- Vacation management
- Clinic settings configuration

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ or SQLite
- Node.js 18+ (optional, for frontend assets)

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/mahmoodhamdi/Clinic-Booking-System.git
cd Clinic-Booking-System
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database
Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinic_booking
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run migrations
```bash
php artisan migrate
```

### 6. Seed the database
```bash
php artisan db:seed
```

Default admin credentials:
- Phone: `01000000000`
- Password: `admin123`

### 7. Start the development server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## API Documentation

See [docs/API.md](docs/API.md) for complete API documentation.

### Quick Start Examples

#### Register a new patient
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ahmed Mohamed",
    "phone": "+201012345678",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+201012345678",
    "password": "password123"
  }'
```

#### Book an appointment
```bash
curl -X POST http://localhost:8000/api/appointments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "appointment_date": "2025-12-25",
    "appointment_time": "10:00"
  }'
```

## Testing

Run the test suite:
```bash
php artisan test
```

Run with coverage:
```bash
php artisan test --coverage
```

Run specific test files:
```bash
php artisan test --filter=DashboardTest
```

Run tests in parallel:
```bash
php artisan test --parallel
```

## Project Structure

```
app/
├── Enums/              # PHP Enums (AppointmentStatus, PaymentMethod, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Api/        # Patient API controllers
│   │   └── Admin/      # Admin API controllers
│   ├── Requests/       # Form request validation
│   └── Resources/      # API resources
├── Models/             # Eloquent models
├── Notifications/      # Notification classes
└── Services/           # Business logic services

database/
├── factories/          # Model factories for testing
├── migrations/         # Database migrations
└── seeders/            # Database seeders

tests/
├── Feature/            # Feature/integration tests
└── Unit/               # Unit tests

docs/
├── API.md              # API documentation
└── *.md                # Phase implementation plans
```

## API Endpoints Summary

| Category | Endpoints | Description |
|----------|-----------|-------------|
| Authentication | 12 | Register, login, logout, password reset, profile |
| Slots (Public) | 4 | Available dates and times |
| Patient Appointments | 6 | Book, view, cancel appointments |
| Patient Profile | 6 | Dashboard, profile management |
| Medical Records | 2 | View patient medical records |
| Prescriptions | 2 | View patient prescriptions |
| Notifications | 5 | View and manage notifications |
| Admin Dashboard | 7 | Statistics, charts, activity |
| Admin Reports | 6 | Reports with PDF export |
| Admin Settings | 4 | Clinic settings management |
| Admin Schedules | 6 | Working hours management |
| Admin Vacations | 5 | Vacation days management |
| Admin Appointments | 11 | Full appointment management |
| Admin Patients | 8 | Patient management |
| Admin Medical Records | 7 | Medical records management |
| Admin Prescriptions | 9 | Prescription management |
| Admin Attachments | 5 | File attachments |
| Admin Payments | 9 | Payment tracking |

**Total: 114 API endpoints**

## Development Phases

| Phase | Description | Status |
|-------|-------------|--------|
| 1 | Project Setup & Authentication | Completed |
| 2 | Clinic Settings & Schedules | Completed |
| 3 | Booking System | Completed |
| 4 | Patient Management | Completed |
| 5 | Medical Records & Prescriptions | Completed |
| 6 | Payments | Completed |
| 7 | Notifications | Completed |
| 8 | Dashboard & Reports | Completed |
| 9 | Final Testing & Polish | Completed |

## Tech Stack

- **Framework**: Laravel 12
- **Authentication**: Laravel Sanctum
- **PDF Generation**: Laravel DomPDF
- **Phone Validation**: Laravel Phone
- **Testing**: PHPUnit

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, please open an issue in the GitHub repository.
