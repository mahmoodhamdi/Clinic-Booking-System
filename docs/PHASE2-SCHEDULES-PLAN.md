# Phase 2: Clinic Settings & Schedules - Implementation Plan

## Overview
This phase implements the clinic scheduling system including clinic settings, weekly schedules, vacations, and automatic time slot generation.

---

## Database Schema

### 1. clinic_settings Table
```sql
CREATE TABLE clinic_settings (
    id BIGINT PRIMARY KEY,
    clinic_name VARCHAR(255) NOT NULL,
    doctor_name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    logo VARCHAR(255),
    slot_duration INT DEFAULT 30,          -- minutes per appointment
    max_patients_per_slot INT DEFAULT 1,
    advance_booking_days INT DEFAULT 30,   -- how far ahead can book
    cancellation_hours INT DEFAULT 24,     -- hours before appointment
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. schedules Table (Weekly Schedule)
```sql
CREATE TABLE schedules (
    id BIGINT PRIMARY KEY,
    day_of_week TINYINT NOT NULL,          -- 0=Sunday, 6=Saturday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    break_start TIME,                       -- optional break
    break_end TIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE(day_of_week)                    -- one schedule per day
);
```

### 3. vacations Table
```sql
CREATE TABLE vacations (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Enums

### DayOfWeek Enum
```php
enum DayOfWeek: int
{
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
}
```

---

## Models

### ClinicSetting Model
- Single record (singleton pattern)
- Accessors for logo URL
- Method: `getInstance()` - get or create settings

### Schedule Model
- Relationships: none
- Scopes: `active()`, `forDay($day)`
- Methods: `getSlots()` - generate time slots
- Casts: day_of_week to DayOfWeek enum

### Vacation Model
- Scopes: `upcoming()`, `active()`, `forDate($date)`
- Methods: `isActive()`, `overlaps($start, $end)`

---

## Services

### SlotGeneratorService
```php
class SlotGeneratorService
{
    // Generate available slots for a specific date
    public function getSlotsForDate(Carbon $date): Collection;

    // Check if a specific slot is available
    public function isSlotAvailable(Carbon $datetime): bool;

    // Get next available slot
    public function getNextAvailableSlot(): ?Carbon;

    // Get available dates for next N days
    public function getAvailableDates(int $days = 30): Collection;
}
```

**Logic:**
1. Check if date is a vacation day → no slots
2. Get schedule for that day of week
3. If no schedule or inactive → no slots
4. Generate slots based on:
   - start_time, end_time
   - slot_duration from settings
   - Exclude break time
5. Filter out past slots (if today)
6. Filter out already booked slots (Phase 3)

---

## API Endpoints

### Clinic Settings (Admin Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/settings | Get clinic settings |
| PUT | /api/admin/settings | Update clinic settings |
| POST | /api/admin/settings/logo | Upload clinic logo |

### Schedules (Admin Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/schedules | List all schedules |
| POST | /api/admin/schedules | Create schedule for a day |
| PUT | /api/admin/schedules/{id} | Update schedule |
| DELETE | /api/admin/schedules/{id} | Delete schedule |
| PUT | /api/admin/schedules/{id}/toggle | Toggle active status |

### Vacations (Admin Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/vacations | List all vacations |
| POST | /api/admin/vacations | Create vacation |
| PUT | /api/admin/vacations/{id} | Update vacation |
| DELETE | /api/admin/vacations/{id} | Delete vacation |

### Public Slots
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/slots/dates | Get available dates |
| GET | /api/slots/{date} | Get available slots for date |

---

## Request Validation

### UpdateSettingsRequest
```php
[
    'clinic_name' => 'required|string|max:255',
    'doctor_name' => 'required|string|max:255',
    'specialization' => 'nullable|string|max:255',
    'phone' => 'nullable|string|max:20',
    'email' => 'nullable|email|max:255',
    'address' => 'nullable|string',
    'slot_duration' => 'required|integer|min:10|max:120',
    'max_patients_per_slot' => 'required|integer|min:1|max:10',
    'advance_booking_days' => 'required|integer|min:1|max:90',
    'cancellation_hours' => 'required|integer|min:0|max:72',
]
```

### StoreScheduleRequest
```php
[
    'day_of_week' => 'required|integer|between:0,6|unique:schedules',
    'start_time' => 'required|date_format:H:i',
    'end_time' => 'required|date_format:H:i|after:start_time',
    'is_active' => 'boolean',
    'break_start' => 'nullable|date_format:H:i|after:start_time',
    'break_end' => 'nullable|date_format:H:i|after:break_start|before:end_time|required_with:break_start',
]
```

### StoreVacationRequest
```php
[
    'title' => 'required|string|max:255',
    'start_date' => 'required|date|after_or_equal:today',
    'end_date' => 'required|date|after_or_equal:start_date',
    'reason' => 'nullable|string',
]
```

---

## API Resources

### ClinicSettingResource
```json
{
    "id": 1,
    "clinic_name": "عيادة الشفاء",
    "doctor_name": "د. أحمد محمد",
    "specialization": "طب عام",
    "phone": "01012345678",
    "email": "clinic@example.com",
    "address": "شارع التحرير، القاهرة",
    "logo_url": "http://localhost/storage/logos/clinic.png",
    "slot_duration": 30,
    "max_patients_per_slot": 1,
    "advance_booking_days": 30,
    "cancellation_hours": 24
}
```

### ScheduleResource
```json
{
    "id": 1,
    "day_of_week": 0,
    "day_name": "الأحد",
    "start_time": "09:00",
    "end_time": "17:00",
    "is_active": true,
    "break_start": "13:00",
    "break_end": "14:00",
    "slots_count": 14
}
```

### VacationResource
```json
{
    "id": 1,
    "title": "عطلة عيد الفطر",
    "start_date": "2025-03-30",
    "end_date": "2025-04-03",
    "reason": "إجازة رسمية",
    "days_count": 5,
    "is_active": true
}
```

### SlotResource
```json
{
    "date": "2025-01-15",
    "day_name": "الأربعاء",
    "time": "09:00",
    "datetime": "2025-01-15T09:00:00",
    "is_available": true
}
```

---

## Testing Strategy

### Unit Tests
1. **DayOfWeekTest** - Enum values and labels
2. **ClinicSettingTest** - Model methods, singleton pattern
3. **ScheduleTest** - Model methods, slot generation
4. **VacationTest** - Model methods, date overlap
5. **SlotGeneratorServiceTest** - Slot generation logic

### Feature Tests
1. **ClinicSettingsApiTest**
   - Admin can get settings
   - Admin can update settings
   - Admin can upload logo
   - Non-admin cannot access

2. **ScheduleApiTest**
   - Admin can CRUD schedules
   - Validation errors handled
   - Cannot create duplicate day
   - Non-admin cannot access

3. **VacationApiTest**
   - Admin can CRUD vacations
   - Cannot create past vacation
   - Non-admin cannot access

4. **SlotApiTest**
   - Get available dates
   - Get slots for specific date
   - No slots on vacation days
   - No slots on inactive days

---

## File Structure
```
app/
├── Enums/
│   └── DayOfWeek.php
├── Http/
│   ├── Controllers/Api/Admin/
│   │   ├── ClinicSettingController.php
│   │   ├── ScheduleController.php
│   │   └── VacationController.php
│   ├── Controllers/Api/
│   │   └── SlotController.php
│   ├── Requests/
│   │   ├── UpdateSettingsRequest.php
│   │   ├── StoreScheduleRequest.php
│   │   ├── UpdateScheduleRequest.php
│   │   ├── StoreVacationRequest.php
│   │   └── UpdateVacationRequest.php
│   └── Resources/
│       ├── ClinicSettingResource.php
│       ├── ScheduleResource.php
│       ├── VacationResource.php
│       └── SlotResource.php
├── Models/
│   ├── ClinicSetting.php
│   ├── Schedule.php
│   └── Vacation.php
└── Services/
    └── SlotGeneratorService.php

database/
├── migrations/
│   ├── YYYY_MM_DD_create_clinic_settings_table.php
│   ├── YYYY_MM_DD_create_schedules_table.php
│   └── YYYY_MM_DD_create_vacations_table.php
├── factories/
│   ├── ClinicSettingFactory.php
│   ├── ScheduleFactory.php
│   └── VacationFactory.php
└── seeders/
    ├── ClinicSettingSeeder.php
    └── ScheduleSeeder.php

tests/
├── Unit/
│   ├── Enums/DayOfWeekTest.php
│   ├── Models/ClinicSettingTest.php
│   ├── Models/ScheduleTest.php
│   ├── Models/VacationTest.php
│   └── Services/SlotGeneratorServiceTest.php
└── Feature/Api/Admin/
    ├── ClinicSettingsApiTest.php
    ├── ScheduleApiTest.php
    ├── VacationApiTest.php
    └── SlotApiTest.php
```

---

## Implementation Order

1. **Enums** - DayOfWeek
2. **Migrations** - clinic_settings, schedules, vacations
3. **Models** - ClinicSetting, Schedule, Vacation
4. **Factories** - All three
5. **Seeders** - Default clinic settings, sample schedules
6. **Services** - SlotGeneratorService
7. **Requests** - All validation classes
8. **Resources** - All API resources
9. **Controllers** - Settings, Schedule, Vacation, Slot
10. **Routes** - Admin and public routes
11. **Tests** - Unit tests then Feature tests

---

## Usage Flow

### Admin Setup
1. Admin updates clinic settings (name, doctor, duration, etc.)
2. Admin creates weekly schedule (e.g., Sun-Thu 9AM-5PM)
3. Admin adds vacations/holidays

### Patient Booking (Phase 3)
1. Patient calls GET /api/slots/dates → available dates
2. Patient calls GET /api/slots/2025-01-15 → available times
3. Patient books a slot (Phase 3 implementation)
