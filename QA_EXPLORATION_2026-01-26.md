# 🔍 QA Exploration Document - Clinic Booking System

**Date:** 2026-01-26
**Project:** Clinic Booking System
**Version:** 1.0.0

---

## 🛠️ TECH STACK IDENTIFIED

### Frontend:
- **Framework:** Next.js 16 (App Router)
- **UI Library:** Radix UI + Tailwind CSS
- **State Management:** Zustand (auth) + React Query (server state)
- **Language:** TypeScript
- **Forms:** React Hook Form + Zod validation
- **i18n:** next-intl (Arabic default, English fallback)

### Backend:
- **Framework:** Laravel 12
- **Language:** PHP 8.2+
- **API Type:** REST
- **Authentication:** Laravel Sanctum

### Database:
- **Type:** MySQL 8.0+ / SQLite
- **ORM:** Eloquent

### Additional:
- **Queue:** Database driver
- **PDF Generation:** DomPDF
- **File Storage:** Local

---

## 🔐 TEST CREDENTIALS

| Role | Phone | Password | Notes |
|------|-------|----------|-------|
| Admin (Doctor) | 01000000000 | admin123 | Full access |
| Secretary | 01100000000 | secretary123 | Admin panel (except settings) |
| Patient 1 | 01200000001 | patient123 | Mohamed - diabetes, blood pressure |
| Patient 2 | 01200000002 | patient123 | Fatma - asthma |
| Patient 3 | 01200000003 | patient123 | Ahmed - heart disease |
| Patient 4 | 01200000004 | patient123 | Noura - food allergies |
| Patient 5 | 01200000005 | patient123 | Khaled - migraine |

---

## 📄 PAGES & ROUTES

### Authentication Pages
| Page | Path | Features |
|------|------|----------|
| Login | /login | Phone + Password, Show/Hide password |
| Register | /register | Full registration form |
| Forgot Password | /forgot-password | OTP request |
| Verify OTP | /verify-otp | OTP verification |
| Reset Password | /reset-password | New password form |

### Patient Portal
| Page | Path | Features |
|------|------|----------|
| Dashboard | /dashboard | Stats, upcoming appointments |
| Appointments | /appointments | List, status tracking |
| Book Appointment | /book | Calendar, slot selection |
| Medical Records | /medical-records | List view |
| Medical Record Detail | /medical-records/[id] | Full record |
| Prescriptions | /prescriptions | List view |
| Prescription Detail | /prescriptions/[id] | Full prescription, PDF download |
| Profile | /profile | Personal info, medical info |
| Notifications | /notifications | Bell icon, mark as read |

### Admin Portal
| Page | Path | Features |
|------|------|----------|
| Dashboard | /admin/dashboard | Stats, charts, today's appointments |
| Appointments | /admin/appointments | List, filter, CRUD |
| Patients | /admin/patients | List, search, filter, profiles |
| Medical Records | /admin/medical-records | CRUD |
| Prescriptions | /admin/prescriptions | CRUD, PDF |
| Payments | /admin/payments | List, track, mark paid |
| Reports | /admin/reports | Appointments, Revenue, Patients |
| Settings | /admin/settings | Clinic settings |
| Vacations | /admin/vacations | CRUD |

---

## 📋 COMPLETE FEATURE MAP

### 🔐 Authentication & Authorization
| Feature | Exists | Location | Notes |
|---------|--------|----------|-------|
| Login | ✅ | /login | Phone-based |
| Register | ✅ | /register | Patient only |
| Forgot Password | ✅ | /forgot-password | OTP via phone |
| Reset Password | ✅ | /reset-password | After OTP |
| OTP Verification | ✅ | /verify-otp | 10-min expiry |
| Logout | ✅ | Header menu | |
| Role-based Access | ✅ | Middleware | admin, secretary, patient |

### 👥 User Roles & Permissions
| Role | Access | Routes |
|------|--------|--------|
| Admin | Full access | /admin/* |
| Secretary | Admin panel (except some settings) | /admin/* |
| Patient | Patient portal only | /*, /appointments, /book |

### 🎨 UI Features
| Feature | Exists | Notes |
|---------|--------|-------|
| Dark Mode | ❓ | Check if implemented |
| RTL Support | ✅ | Arabic is default |
| Multi-language | ✅ | Arabic, English |
| Responsive | ✅ | Mobile-friendly |
| Notifications | ✅ | Bell icon, toast |
| Loading States | ✅ | Spinners, skeletons |

### 📊 Data Features
| Feature | Exists | Modules |
|---------|--------|---------|
| Pagination | ✅ | All list views |
| Search | ✅ | Patients, Appointments |
| Filters | ✅ | Appointments (status, date) |
| Sorting | ✅ | Tables |
| Export PDF | ✅ | Reports, Prescriptions |

### 📝 Forms Identified
| Form | Location | Fields |
|------|----------|--------|
| Login | /login | phone, password |
| Register | /register | name, phone, email, password |
| Book Appointment | /book | date, time, reason, notes |
| Patient Profile | /profile | personal, medical info |
| Medical Record | /admin/medical-records | diagnosis, symptoms, vitals |
| Prescription | /admin/prescriptions | medications, dosage |
| Payment | /admin/payments | amount, method, discount |
| Clinic Settings | /admin/settings | name, slot duration, etc. |

---

## 🔗 API ENDPOINTS (88 Total)

### Public (No Auth)
- POST /api/auth/register
- POST /api/auth/login
- POST /api/auth/forgot-password
- POST /api/auth/verify-otp
- POST /api/auth/reset-password
- GET /api/slots/dates
- GET /api/slots/next
- GET /api/slots/{date}
- POST /api/slots/check
- GET /api/health

### Patient (Auth Required)
- GET/POST /api/appointments
- GET /api/appointments/upcoming
- POST /api/appointments/{id}/cancel
- GET /api/patient/dashboard
- GET/POST/PUT /api/patient/profile
- GET /api/medical-records
- GET /api/prescriptions
- GET /api/notifications

### Admin (Auth + Admin Role)
- GET /api/admin/dashboard/*
- GET /api/admin/reports/*
- GET/PUT /api/admin/settings
- CRUD /api/admin/schedules
- CRUD /api/admin/vacations
- CRUD /api/admin/appointments (+ status actions)
- CRUD /api/admin/patients
- CRUD /api/admin/medical-records
- CRUD /api/admin/prescriptions
- CRUD /api/admin/payments

---

## 📱 PORT CONFIGURATION

- **Port 3000:** BUSY (existing process)
- **Port 3001:** FREE - Use for Frontend
- **Port 8000:** FREE - Use for Backend

---

## 📝 TEST PLAN

### Execution Order:
1. ✅ Environment Setup
2. Authentication Tests (all flows)
3. Role-based Access Tests (Admin, Secretary, Patient)
4. Module Tests (Dashboard, Appointments, Patients, etc.)
5. UI/UX Tests (Language, Responsive)
6. Data Features (Pagination, Search, Filters)
7. Bug Detection & Fix
8. Screenshot Documentation
9. Final Report

### Priority Matrix:
| Priority | Features |
|----------|----------|
| P0 - Critical | Auth, Booking, Payments |
| P1 - High | Dashboard, Appointments, Patients |
| P2 - Medium | Reports, Export, Search |
| P3 - Low | Vacations, Settings |

---

## ✅ EXPLORATION CHECKLIST

- [x] Project structure fully mapped
- [x] Tech stack identified and documented
- [x] All routes/pages discovered
- [x] All features listed
- [x] All user roles identified
- [x] Test user credentials extracted
- [x] All forms catalogued
- [x] API endpoints documented
- [x] Database schema understood
- [x] Test plan created with priorities
