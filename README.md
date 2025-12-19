# Clinic Booking System

A comprehensive clinic management system for single-doctor private practices built with Laravel 12.

## Features

- Patient registration & authentication (phone-based)
- Online appointment booking
- Medical records management
- Prescription generation (PDF)
- Payment tracking
- SMS/WhatsApp notifications
- Admin dashboard with reports

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ or SQLite
- Node.js 18+

## Installation

1. Clone the repository:
```bash
git clone https://github.com/mahmoodhamdi/Clinic-Booking-System.git
cd Clinic-Booking-System
```

2. Install PHP dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env`

6. Run migrations:
```bash
php artisan migrate
```

7. Seed the database (creates admin user):
```bash
php artisan db:seed
```

Default admin credentials:
- Phone: `01000000000`
- Password: `admin123`

## Development

Start the development server:
```bash
php artisan serve
```

## Testing

Run all tests:
```bash
php artisan test
```

Run tests with coverage:
```bash
php artisan test --coverage
```

Run specific test:
```bash
php artisan test --filter=TestClassName
```

## API Documentation

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/register | Register new patient |
| POST | /api/auth/login | Login |
| POST | /api/auth/logout | Logout (auth required) |
| POST | /api/auth/refresh | Refresh token (auth required) |
| GET | /api/auth/me | Get current user (auth required) |
| PUT | /api/auth/profile | Update profile (auth required) |
| POST | /api/auth/change-password | Change password (auth required) |
| POST | /api/auth/avatar | Upload avatar (auth required) |
| DELETE | /api/auth/account | Delete account (auth required) |
| POST | /api/auth/forgot-password | Request password reset |
| POST | /api/auth/verify-otp | Verify OTP |
| POST | /api/auth/reset-password | Reset password |

### API Response Format

```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

## Project Structure

```
app/
├── Enums/           # PHP Enums (UserRole, Gender, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Api/     # REST API controllers
│   │   └── Web/     # Web controllers
│   ├── Middleware/  # Custom middleware
│   ├── Requests/    # Form request validation
│   └── Resources/   # API resources
├── Models/          # Eloquent models
└── Services/        # Business logic services

database/
├── factories/       # Model factories
├── migrations/      # Database migrations
└── seeders/         # Database seeders

tests/
├── Feature/         # Feature tests
└── Unit/            # Unit tests
```

## Tech Stack

- **Framework**: Laravel 12
- **Authentication**: Laravel Sanctum
- **PDF Generation**: Laravel DomPDF
- **Phone Validation**: Laravel Phone
- **Testing**: PHPUnit

## License

MIT
