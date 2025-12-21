# Clinic Booking System - All Links

## Current Development Environment

| Description | URL | Status |
|-------------|-----|--------|
| **Frontend** | http://localhost:3001 | ✅ Running |
| **Backend API** | http://127.0.0.1:8000/api | ✅ Running |
| **API Health** | http://127.0.0.1:8000/api/health | ✅ Available |

## Local Development (Laragon)

| Description | URL |
|-------------|-----|
| **Base URL** | http://localhost:9000 |
| **API Base** | http://localhost:9000/api |
| **Health Check** | http://localhost:9000/api/health |

## Docker Development

| Description | URL |
|-------------|-----|
| **Base URL** | http://localhost:8001 |
| **API Base** | http://localhost:8001/api |
| **Health Check** | http://localhost:8001/api/health |
| **phpMyAdmin** | http://localhost:8080 (with --profile tools) |

---

## Frontend Pages (Next.js 16)

### Public Pages
| Page | URL | Description |
|------|-----|-------------|
| **Home** | http://localhost:3001 | Landing page (redirects to login) |
| **Login** | http://localhost:3001/login | User login page |
| **Register** | http://localhost:3001/register | Patient registration |
| **Forgot Password** | http://localhost:3001/forgot-password | Password reset request |
| **Verify OTP** | http://localhost:3001/verify-otp | OTP verification |
| **Reset Password** | http://localhost:3001/reset-password | Set new password |

### Patient Pages (Authenticated)
| Page | URL | Description |
|------|-----|-------------|
| **Dashboard** | http://localhost:3001/dashboard | Patient dashboard |
| **Book Appointment** | http://localhost:3001/book | Book new appointment |
| **My Appointments** | http://localhost:3001/appointments | View appointments |
| **Medical Records** | http://localhost:3001/medical-records | View medical records |
| **Prescriptions** | http://localhost:3001/prescriptions | View prescriptions |
| **Notifications** | http://localhost:3001/notifications | View notifications |
| **Profile** | http://localhost:3001/profile | Edit profile |

### Admin Pages (Admin/Secretary Only)
| Page | URL | Description |
|------|-----|-------------|
| **Admin Dashboard** | http://localhost:3001/admin/dashboard | Admin overview |
| **Appointments** | http://localhost:3001/admin/appointments | Manage appointments |
| **Patients** | http://localhost:3001/admin/patients | Manage patients |
| **Medical Records** | http://localhost:3001/admin/medical-records | Manage records |
| **Prescriptions** | http://localhost:3001/admin/prescriptions | Manage prescriptions |
| **Payments** | http://localhost:3001/admin/payments | Manage payments |
| **Settings** | http://localhost:3001/admin/settings | Clinic settings |

---

## Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new patient |
| POST | `/api/auth/login` | Login (phone + password) |
| POST | `/api/auth/logout` | Logout (requires token) |
| GET | `/api/auth/me` | Get current user info |
| POST | `/api/auth/verify-otp` | Verify OTP code |
| POST | `/api/auth/forgot-password` | Request password reset |
| POST | `/api/auth/reset-password` | Reset password with OTP |
| POST | `/api/auth/change-password` | Change password (authenticated) |
| PUT | `/api/auth/profile` | Update profile |
| POST | `/api/auth/avatar` | Upload avatar |
| POST | `/api/auth/refresh` | Refresh token |
| DELETE | `/api/auth/account` | Delete account |

---

## Patient Endpoints (Authenticated)

### Slots & Booking
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/slots/dates` | Get available dates |
| GET | `/api/slots/{date}` | Get available slots for date |
| GET | `/api/slots/next` | Get next available slot |
| POST | `/api/slots/check` | Check slot availability |

### Appointments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/appointments` | List my appointments |
| POST | `/api/appointments` | Book new appointment |
| GET | `/api/appointments/upcoming` | Get upcoming appointments |
| GET | `/api/appointments/{id}` | Get appointment details |
| POST | `/api/appointments/{id}/cancel` | Cancel appointment |
| POST | `/api/appointments/check` | Check booking availability |

### Patient Profile
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/patient/profile` | Get my profile |
| POST | `/api/patient/profile` | Create profile |
| PUT | `/api/patient/profile` | Update profile |
| GET | `/api/patient/dashboard` | Patient dashboard |
| GET | `/api/patient/history` | Medical history |
| GET | `/api/patient/statistics` | My statistics |

### Medical Records
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/medical-records` | List my medical records |
| GET | `/api/medical-records/{id}` | Get record details |

### Prescriptions
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/prescriptions` | List my prescriptions |
| GET | `/api/prescriptions/{id}` | Get prescription details |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | List notifications |
| GET | `/api/notifications/unread-count` | Get unread count |
| POST | `/api/notifications/{id}/read` | Mark as read |
| POST | `/api/notifications/read-all` | Mark all as read |
| DELETE | `/api/notifications/{id}` | Delete notification |

---

## Admin Endpoints (Admin/Secretary Only)

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard/stats` | Overview statistics |
| GET | `/api/admin/dashboard/today` | Today's summary |
| GET | `/api/admin/dashboard/chart` | Chart data |
| GET | `/api/admin/dashboard/weekly` | Weekly stats |
| GET | `/api/admin/dashboard/monthly` | Monthly stats |
| GET | `/api/admin/dashboard/upcoming-appointments` | Upcoming appointments |
| GET | `/api/admin/dashboard/recent-activity` | Recent activity |

### Appointments Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/appointments` | List all appointments |
| GET | `/api/admin/appointments/today` | Today's appointments |
| GET | `/api/admin/appointments/upcoming` | Upcoming appointments |
| GET | `/api/admin/appointments/for-date` | Appointments by date |
| GET | `/api/admin/appointments/statistics` | Appointment statistics |
| GET | `/api/admin/appointments/{id}` | Get appointment details |
| POST | `/api/admin/appointments/{id}/confirm` | Confirm appointment |
| POST | `/api/admin/appointments/{id}/complete` | Mark as completed |
| POST | `/api/admin/appointments/{id}/cancel` | Cancel appointment |
| POST | `/api/admin/appointments/{id}/no-show` | Mark as no-show |
| PUT | `/api/admin/appointments/{id}/notes` | Update notes |

### Patients Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/patients` | List all patients |
| GET | `/api/admin/patients/summary` | Patients summary |
| GET | `/api/admin/patients/{id}` | Get patient details |
| GET | `/api/admin/patients/{id}/appointments` | Patient's appointments |
| GET | `/api/admin/patients/{id}/medical-records` | Patient's records |
| GET | `/api/admin/patients/{id}/prescriptions` | Patient's prescriptions |
| GET | `/api/admin/patients/{id}/statistics` | Patient statistics |
| PUT | `/api/admin/patients/{id}/profile` | Update patient profile |
| PUT | `/api/admin/patients/{id}/status` | Toggle patient status |
| POST | `/api/admin/patients/{id}/notes` | Add notes |

### Medical Records
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/medical-records` | List all records |
| POST | `/api/admin/medical-records` | Create new record |
| GET | `/api/admin/medical-records/follow-ups-due` | Due follow-ups |
| GET | `/api/admin/medical-records/{id}` | Get record |
| PUT | `/api/admin/medical-records/{id}` | Update record |
| DELETE | `/api/admin/medical-records/{id}` | Delete record |

### Attachments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/medical-records/{id}/attachments` | List attachments |
| POST | `/api/admin/medical-records/{id}/attachments` | Upload attachment |
| GET | `/api/admin/medical-records/{id}/attachments/{attachmentId}` | Get attachment |
| GET | `/api/admin/medical-records/{id}/attachments/{attachmentId}/download` | Download |
| DELETE | `/api/admin/medical-records/{id}/attachments/{attachmentId}` | Delete |

### Prescriptions
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/prescriptions` | List all prescriptions |
| POST | `/api/admin/prescriptions` | Create prescription |
| GET | `/api/admin/prescriptions/{id}` | Get prescription |
| PUT | `/api/admin/prescriptions/{id}` | Update prescription |
| DELETE | `/api/admin/prescriptions/{id}` | Delete prescription |
| POST | `/api/admin/prescriptions/{id}/dispense` | Mark as dispensed |
| GET | `/api/admin/prescriptions/{id}/pdf` | View PDF |
| GET | `/api/admin/prescriptions/{id}/download` | Download PDF |

### Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/payments` | List all payments |
| POST | `/api/admin/payments` | Create payment |
| GET | `/api/admin/payments/statistics` | Payment statistics |
| GET | `/api/admin/payments/report` | Payment report |
| GET | `/api/admin/payments/{id}` | Get payment |
| PUT | `/api/admin/payments/{id}` | Update payment |
| POST | `/api/admin/payments/{id}/mark-paid` | Mark as paid |
| POST | `/api/admin/payments/{id}/refund` | Process refund |
| GET | `/api/admin/appointments/{id}/payment` | Get appointment payment |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/reports/revenue` | Revenue report |
| GET | `/api/admin/reports/revenue/export` | Export revenue PDF |
| GET | `/api/admin/reports/appointments` | Appointments report |
| GET | `/api/admin/reports/appointments/export` | Export appointments PDF |
| GET | `/api/admin/reports/patients` | Patients report |
| GET | `/api/admin/reports/patients/export` | Export patients PDF |

### Schedules
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/schedules` | List schedules |
| POST | `/api/admin/schedules` | Create schedule |
| GET | `/api/admin/schedules/{id}` | Get schedule |
| PUT | `/api/admin/schedules/{id}` | Update schedule |
| DELETE | `/api/admin/schedules/{id}` | Delete schedule |
| PUT | `/api/admin/schedules/{id}/toggle` | Toggle active status |

### Vacations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/vacations` | List vacations |
| POST | `/api/admin/vacations` | Create vacation |
| GET | `/api/admin/vacations/{id}` | Get vacation |
| PUT | `/api/admin/vacations/{id}` | Update vacation |
| DELETE | `/api/admin/vacations/{id}` | Delete vacation |

### Clinic Settings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/settings` | Get clinic settings |
| PUT | `/api/admin/settings` | Update settings |
| POST | `/api/admin/settings/logo` | Upload logo |
| DELETE | `/api/admin/settings/logo` | Delete logo |

---

## Web Routes (Basic Auth Pages)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/login` | Login page |
| POST | `/login` | Process login |
| GET | `/register` | Registration page |
| POST | `/register` | Process registration |
| POST | `/logout` | Logout |
| GET | `/forgot-password` | Forgot password page |
| POST | `/forgot-password` | Send reset OTP |
| GET | `/verify-otp` | OTP verification page |
| POST | `/verify-otp` | Verify OTP |
| GET | `/reset-password` | Reset password page |
| POST | `/reset-password` | Process password reset |

---

## Documentation

| Description | Path |
|-------------|------|
| **API Documentation** | `docs/API.md` |
| **OpenAPI Specification** | `docs/openapi.yaml` |
| **Docker Guide** | `DOCKER.md` |
| **Progress Tracker** | `PROGRESS.md` |
| **Main README** | `README.md` |

---

## Default Credentials

| Role | Phone | Password |
|------|-------|----------|
| Admin | 01000000000 | admin123 |

---

## Headers

### Required Headers for API Requests

```
Accept: application/json
Content-Type: application/json
```

### Authentication Header (for protected routes)

```
Authorization: Bearer {token}
```

### Localization Header

```
Accept-Language: ar   # Arabic (default)
Accept-Language: en   # English
```

Or use query parameter: `?lang=ar` or `?lang=en`

---

## Example API Calls

### Login
```bash
curl -X POST http://localhost:9000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"phone":"01000000000","password":"admin123"}'
```

### Get Dashboard Stats (Admin)
```bash
curl http://localhost:9000/api/admin/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Available Dates
```bash
curl http://localhost:9000/api/slots/dates \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Book Appointment
```bash
curl -X POST http://localhost:9000/api/appointments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"date":"2025-01-15","slot_time":"09:00","reason":"General checkup"}'
```
