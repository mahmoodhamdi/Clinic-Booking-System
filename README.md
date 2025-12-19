# Clinic Booking System

A comprehensive REST API for private medical clinic management built with Laravel 12.

## Features

### Authentication & User Management
- Phone-based authentication with OTP verification
- Role-based access control (Admin, Secretary, Patient)
- Profile management with avatar upload
- Secure password reset flow

### Appointment Booking
- Real-time slot availability checking
- Advanced booking with configurable lead time
- Appointment status management (pending, confirmed, completed, cancelled, no-show)
- Automated conflict detection

### Patient Management
- Complete patient profiles with medical history
- Blood type, allergies, chronic diseases tracking
- Emergency contact information
- Insurance details management

### Medical Records
- Detailed examination documentation
- Vital signs tracking (BP, heart rate, temperature, weight, height)
- Follow-up scheduling
- File attachments support (images, PDFs, documents)

### Prescriptions
- Digital prescription creation
- Medication details with dosage and instructions
- PDF generation and download
- Dispensing status tracking

### Payments
- Multiple payment methods (Cash, Card, Wallet)
- Discount management
- Payment status tracking (pending, paid, refunded)
- Revenue reporting

### Dashboard & Reports
- Real-time statistics
- Appointment trends and charts
- Revenue reports with date filtering
- PDF export functionality

### Notifications
- In-app notification system
- Appointment reminders
- Status change alerts

### Localization
- Multi-language support (Arabic, English)
- RTL layout support
- Easily extensible for additional languages

## Tech Stack

- **Framework**: Laravel 12
- **PHP Version**: 8.2+
- **Authentication**: Laravel Sanctum
- **Database**: MySQL / SQLite
- **PDF Generation**: DomPDF
- **Testing**: PHPUnit
- **Containerization**: Docker

## Quick Start with Docker

The easiest way to run this project:

```bash
# Clone and enter directory
git clone https://github.com/mahmoodhamdi/Clinic-Booking-System.git
cd Clinic-Booking-System

# Windows
docker-start.bat

# Linux/Mac
chmod +x docker-start.sh
./docker-start.sh
```

Or manually:
```bash
cp .env.example .env
docker-compose up -d --build
```

Access at: **http://localhost:8000**

> If port 8000 is busy, change `APP_PORT` in `.env` to another port (e.g., 8001)

For detailed Docker instructions, see [DOCKER.md](DOCKER.md)

## Manual Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL or SQLite
- Node.js & NPM (optional, for frontend assets)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/clinic-booking-system.git
   cd clinic-booking-system
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**

   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=clinic_booking
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

   The API will be available at `http://localhost:8000/api`

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
All protected endpoints require a Bearer token:
```
Authorization: Bearer {token}
```

### Localization
Set language via header or query parameter:
- Header: `Accept-Language: ar` or `Accept-Language: en`
- Query: `?lang=ar` or `?lang=en`

### Main Endpoints

| Category | Endpoint | Description |
|----------|----------|-------------|
| **Auth** | POST /api/auth/register | Register new patient |
| | POST /api/auth/login | Login |
| | POST /api/auth/logout | Logout |
| | GET /api/auth/me | Get current user |
| **Slots** | GET /api/slots/dates | Get available dates |
| | GET /api/slots/{date} | Get slots for date |
| **Appointments** | GET /api/appointments | List my appointments |
| | POST /api/appointments | Book appointment |
| | POST /api/appointments/{id}/cancel | Cancel appointment |
| **Medical Records** | GET /api/medical-records | List my records |
| **Prescriptions** | GET /api/prescriptions | List my prescriptions |
| **Notifications** | GET /api/notifications | List notifications |
| **Locales** | GET /api/locales | Get supported languages |

### Admin Endpoints

| Category | Endpoint | Description |
|----------|----------|-------------|
| **Dashboard** | GET /api/admin/dashboard/stats | Overview statistics |
| **Appointments** | GET /api/admin/appointments | List all appointments |
| **Patients** | GET /api/admin/patients | List all patients |
| **Medical Records** | POST /api/admin/medical-records | Create record |
| **Prescriptions** | POST /api/admin/prescriptions | Create prescription |
| **Payments** | GET /api/admin/payments | List payments |
| **Reports** | GET /api/admin/reports/revenue | Revenue report |
| **Settings** | GET /api/admin/settings | Clinic settings |

For complete API documentation, see:
- [API Documentation](docs/API.md)
- [OpenAPI Specification](docs/openapi.yaml)

## Testing

Run the test suite:
```bash
php artisan test
```

Run with coverage:
```bash
php artisan test --coverage
```

Run specific test:
```bash
php artisan test --filter=AppointmentTest
```

**Current Status**: 544 tests passing with 1615 assertions

## Project Structure

```
app/
├── Enums/              # Enumerations (UserRole, AppointmentStatus, etc.)
├── Http/
│   ├── Controllers/    # API Controllers
│   ├── Middleware/     # Custom middleware
│   ├── Requests/       # Form request validation
│   └── Resources/      # API Resources
├── Models/             # Eloquent models
└── Services/           # Business logic services

config/
├── localization.php    # Multi-language configuration

database/
├── factories/          # Model factories
├── migrations/         # Database migrations
└── seeders/            # Database seeders

docs/
├── API.md              # API documentation
├── openapi.yaml        # OpenAPI 3.0 specification
└── PHASE*.md           # Implementation plans

lang/
├── ar.json             # Arabic translations
└── en.json             # English translations

tests/
├── Feature/            # Feature tests
└── Unit/               # Unit tests
```

## Configuration

### Clinic Settings
Configure via API or directly in database:
- Clinic name and contact info
- Slot duration (default: 30 minutes)
- Maximum patients per slot
- Advance booking days
- Cancellation hours limit

### Localization Settings
Edit `config/localization.php`:
```php
'supported' => [
    'ar' => ['name' => 'Arabic', 'direction' => 'rtl', ...],
    'en' => ['name' => 'English', 'direction' => 'ltr', ...],
],
'default' => 'ar',
'fallback' => 'en',
```

## Default Admin Credentials

After running seeders:
- **Phone**: 01000000000
- **Password**: admin123

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, email hmdy7486@gmail.com or open an issue on GitHub.
