# Clinic Booking System API Documentation

**Version**: 1.0.0
**Base URL**: `http://localhost:8000/api`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Rate Limiting](#rate-limiting)
5. [Public Endpoints](#public-endpoints)
6. [Patient Endpoints](#patient-endpoints)
7. [Admin Endpoints](#admin-endpoints)
8. [Error Codes](#error-codes)

---

## Overview

The Clinic Booking System API provides endpoints for managing clinic appointments, patients, medical records, prescriptions, and payments. The API uses Laravel Sanctum for authentication.

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| `Authorization` | Yes (protected) | `Bearer {token}` |
| `Accept` | Recommended | `application/json` |
| `Accept-Language` | Optional | `ar` (default) or `en` |
| `Content-Type` | Yes (POST/PUT) | `application/json` |

---

## Authentication

All protected endpoints require a valid Sanctum token in the `Authorization` header.

### Login

```
POST /auth/login
```

**Request Body:**
```json
{
  "phone": "01012345678",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "phone": "01012345678",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abc123..."
  }
}
```

---

## Response Format

All API responses follow this format:

```json
{
  "success": true|false,
  "message": "Response message",
  "data": { ... }
}
```

### Paginated Response

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

---

## Rate Limiting

| Group | Limit | Applies To |
|-------|-------|------------|
| `auth` | 5/minute | Login, Register, Password Reset |
| `api` | 60/minute | General API calls |
| `slots` | 30/minute | Slot availability checks |
| `booking` | 3/minute | Appointment booking |

---

## Public Endpoints

### Health Check

```
GET /health
```

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register new patient |
| POST | `/auth/login` | Login user |
| POST | `/auth/forgot-password` | Request password reset OTP |
| POST | `/auth/verify-otp` | Verify OTP code |
| POST | `/auth/reset-password` | Reset password with OTP |

### Slots (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/slots/dates` | Get available booking dates |
| GET | `/slots/next` | Get next available date |
| GET | `/slots/{date}` | Get available slots for date |
| POST | `/slots/check` | Check slot availability |

**Get Available Dates:**
```
GET /slots/dates
```

**Response:**
```json
{
  "success": true,
  "data": [
    "2026-01-21",
    "2026-01-22",
    "2026-01-23"
  ]
}
```

**Get Slots for Date:**
```
GET /slots/2026-01-21
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "time": "09:00",
      "end_time": "09:30",
      "available": true,
      "remaining": 1
    },
    {
      "time": "09:30",
      "end_time": "10:00",
      "available": false,
      "remaining": 0
    }
  ]
}
```

---

## Patient Endpoints

*Requires authentication (`auth:sanctum`)*

### Authentication (Protected)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/logout` | Logout user |
| POST | `/auth/refresh` | Refresh token |
| GET | `/auth/me` | Get current user |
| PUT | `/auth/profile` | Update profile |
| POST | `/auth/change-password` | Change password |
| POST | `/auth/avatar` | Upload avatar |
| DELETE | `/auth/account` | Delete account |

### Appointments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/appointments` | List patient appointments |
| GET | `/appointments/upcoming` | Get upcoming appointments |
| POST | `/appointments` | Book new appointment |
| POST | `/appointments/check` | Check booking eligibility |
| GET | `/appointments/{id}` | Get appointment details |
| POST | `/appointments/{id}/cancel` | Cancel appointment |

**Book Appointment:**
```
POST /appointments
```

**Request Body:**
```json
{
  "date": "2026-01-25",
  "slot_time": "10:00",
  "reason": "كشف عام",
  "notes": "ملاحظات اختيارية"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم حجز الموعد بنجاح",
  "data": {
    "id": 123,
    "appointment_date": "2026-01-25",
    "appointment_time": "10:00:00",
    "status": "pending",
    "reason": "كشف عام"
  }
}
```

### Patient Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/patient/dashboard` | Get dashboard summary |
| GET | `/patient/profile` | Get patient profile |
| POST | `/patient/profile` | Create profile |
| PUT | `/patient/profile` | Update profile |
| GET | `/patient/history` | Get appointment history |
| GET | `/patient/statistics` | Get patient statistics |

### Medical Records

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/medical-records` | List medical records |
| GET | `/medical-records/{id}` | Get record details |

### Prescriptions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/prescriptions` | List prescriptions |
| GET | `/prescriptions/{id}` | Get prescription details |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | List notifications |
| GET | `/notifications/unread-count` | Get unread count |
| POST | `/notifications/{id}/read` | Mark as read |
| POST | `/notifications/read-all` | Mark all as read |
| DELETE | `/notifications/{id}` | Delete notification |

---

## Admin Endpoints

*Requires authentication + admin/secretary role (`auth:sanctum`, `admin`)*

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/dashboard/stats` | Get overall statistics |
| GET | `/admin/dashboard/today` | Get today's summary |
| GET | `/admin/dashboard/weekly` | Get weekly summary |
| GET | `/admin/dashboard/monthly` | Get monthly summary |
| GET | `/admin/dashboard/chart` | Get chart data |
| GET | `/admin/dashboard/recent-activity` | Get recent activity |
| GET | `/admin/dashboard/upcoming-appointments` | Get upcoming appointments |

### Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/reports/appointments` | Appointments report |
| GET | `/admin/reports/revenue` | Revenue report |
| GET | `/admin/reports/patients` | Patients report |
| GET | `/admin/reports/appointments/export` | Export appointments |
| GET | `/admin/reports/revenue/export` | Export revenue |
| GET | `/admin/reports/patients/export` | Export patients |

### Clinic Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/settings` | Get clinic settings |
| PUT | `/admin/settings` | Update settings |
| POST | `/admin/settings/logo` | Upload logo |
| DELETE | `/admin/settings/logo` | Delete logo |

### Schedules

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/schedules` | List all schedules |
| POST | `/admin/schedules` | Create schedule |
| GET | `/admin/schedules/{id}` | Get schedule |
| PUT | `/admin/schedules/{id}` | Update schedule |
| DELETE | `/admin/schedules/{id}` | Delete schedule |
| PUT | `/admin/schedules/{id}/toggle` | Toggle active status |

**Create Schedule:**
```
POST /admin/schedules
```

**Request Body:**
```json
{
  "day_of_week": 0,
  "start_time": "09:00",
  "end_time": "17:00",
  "break_start": "13:00",
  "break_end": "14:00",
  "is_active": true
}
```

### Vacations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/vacations` | List vacations |
| POST | `/admin/vacations` | Create vacation |
| GET | `/admin/vacations/{id}` | Get vacation |
| PUT | `/admin/vacations/{id}` | Update vacation |
| DELETE | `/admin/vacations/{id}` | Delete vacation |

### Appointments (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/appointments` | List all appointments |
| GET | `/admin/appointments/today` | Today's appointments |
| GET | `/admin/appointments/upcoming` | Upcoming appointments |
| GET | `/admin/appointments/for-date` | Appointments for date |
| GET | `/admin/appointments/statistics` | Statistics |
| GET | `/admin/appointments/{id}` | Get appointment |
| POST | `/admin/appointments/{id}/confirm` | Confirm appointment |
| POST | `/admin/appointments/{id}/complete` | Complete appointment |
| POST | `/admin/appointments/{id}/cancel` | Cancel appointment |
| POST | `/admin/appointments/{id}/no-show` | Mark no-show |
| PUT | `/admin/appointments/{id}/notes` | Update notes |
| POST | `/admin/appointments/{id}/reschedule` | Reschedule |

**Reschedule Appointment:**
```
POST /admin/appointments/{id}/reschedule
```

**Request Body:**
```json
{
  "date": "2026-01-28",
  "slot_time": "14:00"
}
```

### Patients (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/patients` | List all patients |
| GET | `/admin/patients/summary` | Patients summary |
| GET | `/admin/patients/{id}` | Get patient |
| GET | `/admin/patients/{id}/appointments` | Patient appointments |
| GET | `/admin/patients/{id}/statistics` | Patient statistics |
| PUT | `/admin/patients/{id}/profile` | Update profile |
| PUT | `/admin/patients/{id}/status` | Toggle status |
| POST | `/admin/patients/{id}/notes` | Add notes |

### Medical Records (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/medical-records` | List records |
| GET | `/admin/medical-records/follow-ups-due` | Due follow-ups |
| POST | `/admin/medical-records` | Create record |
| GET | `/admin/medical-records/{id}` | Get record |
| PUT | `/admin/medical-records/{id}` | Update record |
| DELETE | `/admin/medical-records/{id}` | Delete record |
| GET | `/admin/patients/{id}/medical-records` | Records by patient |

**Create Medical Record:**
```
POST /admin/medical-records
```

**Request Body:**
```json
{
  "appointment_id": 123,
  "diagnosis": "التشخيص",
  "symptoms": "الأعراض",
  "treatment_plan": "خطة العلاج",
  "blood_pressure_systolic": 120,
  "blood_pressure_diastolic": 80,
  "heart_rate": 72,
  "temperature": 37.0,
  "weight": 70,
  "height": 170,
  "follow_up_date": "2026-02-01",
  "follow_up_notes": "ملاحظات المتابعة"
}
```

### Prescriptions (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/prescriptions` | List prescriptions |
| POST | `/admin/prescriptions` | Create prescription |
| GET | `/admin/prescriptions/{id}` | Get prescription |
| PUT | `/admin/prescriptions/{id}` | Update prescription |
| DELETE | `/admin/prescriptions/{id}` | Delete prescription |
| POST | `/admin/prescriptions/{id}/dispense` | Mark dispensed |
| GET | `/admin/prescriptions/{id}/pdf` | Stream PDF |
| GET | `/admin/prescriptions/{id}/download` | Download PDF |
| GET | `/admin/patients/{id}/prescriptions` | By patient |

### Attachments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/medical-records/{id}/attachments` | List attachments |
| POST | `/admin/medical-records/{id}/attachments` | Upload attachment |
| GET | `/admin/medical-records/{id}/attachments/{aid}` | Get attachment |
| DELETE | `/admin/medical-records/{id}/attachments/{aid}` | Delete attachment |
| GET | `/admin/medical-records/{id}/attachments/{aid}/download` | Download |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/payments` | List payments |
| GET | `/admin/payments/statistics` | Statistics |
| GET | `/admin/payments/report` | Payment report |
| POST | `/admin/payments` | Create payment |
| POST | `/admin/payments/record` | Record direct payment |
| GET | `/admin/payments/{id}` | Get payment |
| PUT | `/admin/payments/{id}` | Update payment |
| POST | `/admin/payments/{id}/mark-paid` | Mark as paid |
| POST | `/admin/payments/{id}/refund` | Refund payment |
| GET | `/admin/appointments/{id}/payment` | Payment by appointment |

**Record Direct Payment:**
```
POST /admin/payments/record
```

**Request Body:**
```json
{
  "patient_id": 123,
  "amount": 150.00,
  "payment_method": "cash",
  "notes": "Payment notes"
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

### Validation Error Response

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "phone": ["رقم الهاتف مطلوب"],
    "password": ["كلمة المرور يجب أن تكون 8 أحرف على الأقل"]
  }
}
```

---

## Appointment Status Flow

```
pending → confirmed → completed
    ↓         ↓
 cancelled   no_show
```

| Status | Description |
|--------|-------------|
| `pending` | Awaiting confirmation |
| `confirmed` | Confirmed by admin |
| `completed` | Visit completed |
| `cancelled` | Cancelled by patient/admin |
| `no_show` | Patient didn't show up |

---

## Payment Status Flow

```
pending → paid → refunded
```

| Status | Description |
|--------|-------------|
| `pending` | Awaiting payment |
| `paid` | Payment received |
| `refunded` | Payment refunded |

---

## Payment Methods

| Method | Description |
|--------|-------------|
| `cash` | Cash payment |
| `card` | Card payment |
| `wallet` | Digital wallet |

---

## User Roles

| Role | Permissions |
|------|-------------|
| `admin` | Full access |
| `secretary` | Admin access (except settings) |
| `patient` | Patient-only access |

---

**Generated**: 2026-01-20
**Total Endpoints**: 88
