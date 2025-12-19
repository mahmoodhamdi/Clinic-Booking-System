# Phase 3: Booking System - Implementation Plan

## Overview
نظام الحجز الكامل يتيح للمرضى حجز المواعيد وللإدارة إدارة جميع الحجوزات.

## 1. Database Design

### appointments table
```
id                  - bigint, primary key
user_id             - foreign key to users (patient)
appointment_date    - date
appointment_time    - time
status              - enum (pending, confirmed, completed, cancelled, no_show)
notes               - text, nullable (patient notes)
admin_notes         - text, nullable (admin/doctor notes)
cancellation_reason - text, nullable
cancelled_by        - enum (patient, admin), nullable
cancelled_at        - timestamp, nullable
confirmed_at        - timestamp, nullable
completed_at        - timestamp, nullable
created_at          - timestamp
updated_at          - timestamp
```

## 2. Enums

### AppointmentStatus
```php
enum AppointmentStatus: string
{
    case PENDING = 'pending';       // في الانتظار
    case CONFIRMED = 'confirmed';   // مؤكد
    case COMPLETED = 'completed';   // مكتمل
    case CANCELLED = 'cancelled';   // ملغي
    case NO_SHOW = 'no_show';       // لم يحضر
}
```

### CancelledBy
```php
enum CancelledBy: string
{
    case PATIENT = 'patient';
    case ADMIN = 'admin';
}
```

## 3. Models

### Appointment Model
- Relationships: belongsTo User (patient)
- Scopes: pending, confirmed, completed, cancelled, noShow, forDate, forPatient, upcoming, past
- Accessors: formatted_date, formatted_time, status_label, can_cancel
- Methods: confirm(), complete(), cancel(), markNoShow()

## 4. Services

### AppointmentService
```php
class AppointmentService
{
    // Booking
    public function book(User $patient, Carbon $datetime, ?string $notes = null): Appointment

    // Status Management
    public function confirm(Appointment $appointment): Appointment
    public function complete(Appointment $appointment, ?string $notes = null): Appointment
    public function cancel(Appointment $appointment, string $reason, CancelledBy $by): Appointment
    public function markNoShow(Appointment $appointment): Appointment

    // Queries
    public function getPatientAppointments(User $patient, ?string $status = null): Collection
    public function getAppointmentsForDate(Carbon $date): Collection
    public function getTodayAppointments(): Collection
    public function getUpcomingAppointments(?int $days = 7): Collection

    // Validation
    public function canBook(User $patient, Carbon $datetime): bool
    public function canCancel(Appointment $appointment): bool

    // Statistics
    public function getStatistics(?Carbon $from = null, ?Carbon $to = null): array
}
```

## 5. API Endpoints

### Public Booking APIs (Authenticated Patient)
```
POST   /api/appointments              - Book new appointment
GET    /api/appointments              - List patient's appointments
GET    /api/appointments/{id}         - Get appointment details
POST   /api/appointments/{id}/cancel  - Cancel appointment
```

### Admin Appointment APIs
```
GET    /api/admin/appointments                    - List all appointments (with filters)
GET    /api/admin/appointments/today              - Get today's appointments
GET    /api/admin/appointments/upcoming           - Get upcoming appointments
GET    /api/admin/appointments/statistics         - Get booking statistics
GET    /api/admin/appointments/{id}               - Get appointment details
POST   /api/admin/appointments/{id}/confirm       - Confirm appointment
POST   /api/admin/appointments/{id}/complete      - Complete appointment
POST   /api/admin/appointments/{id}/cancel        - Cancel appointment (admin)
POST   /api/admin/appointments/{id}/no-show       - Mark as no-show
PUT    /api/admin/appointments/{id}/notes         - Update admin notes
```

## 6. Request Validation Classes

### BookAppointmentRequest
- datetime: required, date, after:now, within advance_booking_days
- notes: nullable, string, max:500

### CancelAppointmentRequest
- reason: required, string, max:500

### UpdateAppointmentNotesRequest
- admin_notes: required, string, max:1000

### ListAppointmentsRequest (Admin)
- status: nullable, in:pending,confirmed,completed,cancelled,no_show
- date: nullable, date
- from_date: nullable, date
- to_date: nullable, date, after_or_equal:from_date
- patient_id: nullable, exists:users,id
- per_page: nullable, integer, min:1, max:100

## 7. API Resources

### AppointmentResource
```json
{
    "id": 1,
    "patient": {
        "id": 1,
        "name": "محمد أحمد",
        "phone": "+201019793768"
    },
    "date": "2025-12-20",
    "time": "09:00",
    "datetime": "2025-12-20T09:00:00+02:00",
    "day_name": "السبت",
    "status": "pending",
    "status_label": "في الانتظار",
    "notes": "ملاحظات المريض",
    "admin_notes": "ملاحظات الطبيب",
    "can_cancel": true,
    "confirmed_at": null,
    "completed_at": null,
    "cancelled_at": null,
    "cancellation_reason": null,
    "created_at": "2025-12-19T10:00:00+02:00"
}
```

### AppointmentCollection
- With pagination
- With summary (total, by status)

## 8. Business Rules

### Booking Rules
1. Only authenticated patients can book
2. Cannot book in the past
3. Cannot book beyond advance_booking_days
4. Cannot book on vacation days
5. Cannot book outside schedule hours
6. Cannot double-book the same slot
7. Patient cannot have more than one pending/confirmed appointment at the same time
8. Patient cannot book if they have 3+ no-shows in the last 30 days

### Cancellation Rules
1. Patient can cancel only pending/confirmed appointments
2. Patient can cancel only their own appointments
3. Admin can cancel any pending/confirmed appointment
4. Cannot cancel completed appointments
5. Must provide cancellation reason

### Confirmation Rules
1. Only admin can confirm appointments
2. Only pending appointments can be confirmed

### Completion Rules
1. Only admin can complete appointments
2. Only confirmed appointments can be completed
3. Cannot complete future appointments

## 9. Update SlotGeneratorService

Update `isSlotAvailable()` and `getSlotsForDate()` to check for existing appointments:
```php
// In isSlotAvailable()
$isBooked = Appointment::where('appointment_date', $date->toDateString())
    ->where('appointment_time', $time)
    ->whereIn('status', [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED])
    ->exists();

if ($isBooked) {
    return false;
}
```

## 10. Files to Create

### Enums
- `app/Enums/AppointmentStatus.php`
- `app/Enums/CancelledBy.php`

### Migration
- `database/migrations/xxxx_create_appointments_table.php`

### Model
- `app/Models/Appointment.php`

### Factory
- `database/factories/AppointmentFactory.php`

### Service
- `app/Services/AppointmentService.php`

### Requests
- `app/Http/Requests/Api/BookAppointmentRequest.php`
- `app/Http/Requests/Api/CancelAppointmentRequest.php`
- `app/Http/Requests/Api/Admin/ListAppointmentsRequest.php`
- `app/Http/Requests/Api/Admin/UpdateAppointmentNotesRequest.php`

### Resources
- `app/Http/Resources/AppointmentResource.php`
- `app/Http/Resources/AppointmentCollection.php`

### Controllers
- `app/Http/Controllers/Api/AppointmentController.php`
- `app/Http/Controllers/Api/Admin/AppointmentController.php`

### Tests
- `tests/Unit/Enums/AppointmentStatusTest.php`
- `tests/Unit/Models/AppointmentTest.php`
- `tests/Unit/Services/AppointmentServiceTest.php`
- `tests/Feature/Api/AppointmentApiTest.php`
- `tests/Feature/Api/Admin/AppointmentApiTest.php`

## 11. Implementation Order

1. Create AppointmentStatus and CancelledBy enums
2. Create appointments migration
3. Create Appointment model with relationships and scopes
4. Create AppointmentFactory
5. Create AppointmentService
6. Update SlotGeneratorService to check appointments
7. Create Request classes
8. Create API Resources
9. Create Patient AppointmentController
10. Create Admin AppointmentController
11. Add routes to api.php
12. Write Unit Tests
13. Write Feature Tests
14. Run all tests
15. Commit and push

## 12. Flow Examples

### Patient Booking Flow
1. Patient calls `GET /api/slots/dates` to see available dates
2. Patient calls `GET /api/slots/{date}` to see available slots
3. Patient calls `POST /api/appointments` with datetime
4. System validates and creates pending appointment
5. Admin confirms via `POST /api/admin/appointments/{id}/confirm`
6. Patient attends, admin completes via `POST /api/admin/appointments/{id}/complete`

### Cancellation Flow
1. Patient/Admin calls cancel endpoint with reason
2. System updates status, sets cancelled_at and cancellation_reason
3. Slot becomes available again for other patients

## 13. Statistics Response Example
```json
{
    "total": 150,
    "by_status": {
        "pending": 10,
        "confirmed": 25,
        "completed": 100,
        "cancelled": 12,
        "no_show": 3
    },
    "today": {
        "total": 8,
        "pending": 2,
        "confirmed": 6
    },
    "this_week": 35,
    "this_month": 150
}
```
