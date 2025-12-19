# Clinic Booking System API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Authentication APIs

### Register
```http
POST /api/auth/register
```
**Body:**
```json
{
  "name": "Ahmed Mohamed",
  "phone": "+201012345678",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Login
```http
POST /api/auth/login
```
**Body:**
```json
{
  "phone": "+201012345678",
  "password": "password123"
}
```

### Logout
```http
POST /api/auth/logout
```
*Requires authentication*

### Get Current User
```http
GET /api/auth/me
```
*Requires authentication*

### Update Profile
```http
PUT /api/auth/profile
```
*Requires authentication*

### Change Password
```http
POST /api/auth/change-password
```
*Requires authentication*

**Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

---

## Slots APIs (Public)

### Get Available Dates
```http
GET /api/slots/dates
```

### Get Next Available Slot
```http
GET /api/slots/next
```

### Get Slots for Date
```http
GET /api/slots/{date}
```
**Parameters:**
- `date`: Date in YYYY-MM-DD format

### Check Slot Availability
```http
POST /api/slots/check
```
**Body:**
```json
{
  "date": "2025-12-25",
  "time": "10:00"
}
```

---

## Patient Appointments APIs

### List My Appointments
```http
GET /api/appointments
```
*Requires authentication*

**Query Parameters:**
- `status`: Filter by status (pending, confirmed, completed, cancelled)
- `per_page`: Items per page (default: 15)

### Get Upcoming Appointments
```http
GET /api/appointments/upcoming
```
*Requires authentication*

### Book Appointment
```http
POST /api/appointments
```
*Requires authentication*

**Body:**
```json
{
  "appointment_date": "2025-12-25",
  "appointment_time": "10:00",
  "notes": "First visit"
}
```

### Get Appointment Details
```http
GET /api/appointments/{id}
```
*Requires authentication*

### Cancel Appointment
```http
POST /api/appointments/{id}/cancel
```
*Requires authentication*

**Body:**
```json
{
  "reason": "Personal reasons"
}
```

---

## Patient Medical Records APIs

### List My Medical Records
```http
GET /api/medical-records
```
*Requires authentication*

### Get Medical Record Details
```http
GET /api/medical-records/{id}
```
*Requires authentication*

---

## Patient Prescriptions APIs

### List My Prescriptions
```http
GET /api/prescriptions
```
*Requires authentication*

### Get Prescription Details
```http
GET /api/prescriptions/{id}
```
*Requires authentication*

---

## Patient Notifications APIs

### List Notifications
```http
GET /api/notifications
```
*Requires authentication*

### Get Unread Count
```http
GET /api/notifications/unread-count
```
*Requires authentication*

### Mark as Read
```http
POST /api/notifications/{id}/read
```
*Requires authentication*

### Mark All as Read
```http
POST /api/notifications/read-all
```
*Requires authentication*

### Delete Notification
```http
DELETE /api/notifications/{id}
```
*Requires authentication*

---

## Admin Dashboard APIs

### Get Overview Statistics
```http
GET /api/admin/dashboard/stats
```
*Requires admin authentication*

**Response:**
```json
{
  "data": {
    "total_patients": 150,
    "total_appointments": 500,
    "total_revenue": 50000.00,
    "pending_appointments": 25,
    "today_appointments": 10,
    "this_week_appointments": 45,
    "this_month_revenue": 15000.00,
    "this_month_appointments": 120
  }
}
```

### Get Today Statistics
```http
GET /api/admin/dashboard/today
```
*Requires admin authentication*

### Get Weekly Statistics
```http
GET /api/admin/dashboard/weekly
```
*Requires admin authentication*

### Get Monthly Statistics
```http
GET /api/admin/dashboard/monthly
```
*Requires admin authentication*

**Query Parameters:**
- `month`: Month number (1-12)
- `year`: Year

### Get Chart Data
```http
GET /api/admin/dashboard/chart
```
*Requires admin authentication*

**Query Parameters:**
- `period`: 'week' or 'month'

### Get Recent Activity
```http
GET /api/admin/dashboard/recent-activity
```
*Requires admin authentication*

**Query Parameters:**
- `limit`: Number of items (default: 10)

---

## Admin Reports APIs

### Appointments Report
```http
GET /api/admin/reports/appointments
```
*Requires admin authentication*

**Query Parameters:**
- `from_date`: Start date (YYYY-MM-DD)
- `to_date`: End date (YYYY-MM-DD)
- `status`: Filter by status

### Revenue Report
```http
GET /api/admin/reports/revenue
```
*Requires admin authentication*

**Query Parameters:**
- `from_date`: Start date (YYYY-MM-DD)
- `to_date`: End date (YYYY-MM-DD)
- `group_by`: 'day', 'week', or 'month'

### Patients Report
```http
GET /api/admin/reports/patients
```
*Requires admin authentication*

**Query Parameters:**
- `from_date`: Start date (YYYY-MM-DD)
- `to_date`: End date (YYYY-MM-DD)

### Export Reports (PDF)
```http
GET /api/admin/reports/appointments/export
GET /api/admin/reports/revenue/export
GET /api/admin/reports/patients/export
```
*Requires admin authentication*

---

## Admin Payments APIs

### List Payments
```http
GET /api/admin/payments
```
*Requires admin authentication*

**Query Parameters:**
- `status`: pending, paid, refunded
- `method`: cash, card, wallet
- `from_date`: Start date
- `to_date`: End date

### Create Payment
```http
POST /api/admin/payments
```
*Requires admin authentication*

**Body:**
```json
{
  "appointment_id": 1,
  "amount": 150.00,
  "discount": 15.00,
  "method": "cash",
  "notes": "Regular checkup"
}
```

### Mark as Paid
```http
POST /api/admin/payments/{id}/mark-paid
```
*Requires admin authentication*

### Refund Payment
```http
POST /api/admin/payments/{id}/refund
```
*Requires admin authentication*

**Body:**
```json
{
  "reason": "Patient request"
}
```

### Get Payment Statistics
```http
GET /api/admin/payments/statistics
```
*Requires admin authentication*

---

## Response Format

### Success Response
```json
{
  "data": { ... },
  "message": "Success message (optional)"
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Pagination Response
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

---

## Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |
