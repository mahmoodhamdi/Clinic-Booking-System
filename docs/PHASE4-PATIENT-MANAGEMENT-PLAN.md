# Phase 4: Patient Management - Implementation Plan

> **Status: COMPLETED**
> All tasks in this phase have been successfully implemented and tested.

## Overview
نظام إدارة المرضى يتيح للمرضى إدارة ملفاتهم الشخصية وللإدارة عرض وإدارة بيانات جميع المرضى.

## 1. Database Design

### patient_profiles table
```
id                    - bigint, primary key
user_id               - foreign key to users (unique)
blood_type            - enum (A+, A-, B+, B-, AB+, AB-, O+, O-)
emergency_contact_name  - string, nullable
emergency_contact_phone - string, nullable
allergies             - text, nullable (JSON array)
chronic_diseases      - text, nullable (JSON array)
current_medications   - text, nullable (JSON array)
medical_notes         - text, nullable
insurance_provider    - string, nullable
insurance_number      - string, nullable
created_at            - timestamp
updated_at            - timestamp
```

## 2. Enums

### BloodType
```php
enum BloodType: string
{
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';
}
```

## 3. Models

### PatientProfile Model
- Relationships: belongsTo User
- Accessors: allergies_list, chronic_diseases_list, current_medications_list
- Casts: allergies, chronic_diseases, current_medications as array

### User Model Updates
- Add hasOne PatientProfile relationship
- Add hasMany Appointments relationship

## 4. API Endpoints

### Patient Profile APIs (Authenticated Patient)
```
GET    /api/patient/profile           - Get patient profile
POST   /api/patient/profile           - Create patient profile
PUT    /api/patient/profile           - Update patient profile
GET    /api/patient/dashboard         - Get patient dashboard (stats, upcoming appointments)
GET    /api/patient/history           - Get appointment history
```

### Admin Patient Management APIs
```
GET    /api/admin/patients                    - List all patients (with filters & search)
GET    /api/admin/patients/{id}               - Get patient details with profile
GET    /api/admin/patients/{id}/appointments  - Get patient appointments
GET    /api/admin/patients/{id}/statistics    - Get patient statistics
PUT    /api/admin/patients/{id}/profile       - Update patient profile (admin)
PUT    /api/admin/patients/{id}/status        - Toggle patient active status
POST   /api/admin/patients/{id}/notes         - Add admin notes to profile
```

## 5. Request Validation Classes

### CreatePatientProfileRequest
- blood_type: nullable, enum
- emergency_contact_name: nullable, string, max:100
- emergency_contact_phone: nullable, string, regex:phone
- allergies: nullable, array
- chronic_diseases: nullable, array
- current_medications: nullable, array
- medical_notes: nullable, string, max:2000
- insurance_provider: nullable, string, max:100
- insurance_number: nullable, string, max:50

### UpdatePatientProfileRequest
- Same as create but all optional

### ListPatientsRequest (Admin)
- search: nullable, string (name, phone, email)
- status: nullable, in:active,inactive
- has_profile: nullable, boolean
- blood_type: nullable, enum
- per_page: nullable, integer, min:1, max:100
- order_by: nullable, in:name,created_at,appointments_count
- order_dir: nullable, in:asc,desc

## 6. API Resources

### PatientProfileResource
```json
{
    "id": 1,
    "blood_type": "A+",
    "blood_type_label": "A موجب",
    "emergency_contact": {
        "name": "محمد أحمد",
        "phone": "+201019793768"
    },
    "allergies": ["البنسلين", "الأسبرين"],
    "chronic_diseases": ["السكري", "ضغط الدم"],
    "current_medications": ["ميتفورمين 500mg"],
    "medical_notes": "ملاحظات طبية",
    "insurance": {
        "provider": "شركة التأمين",
        "number": "INS-123456"
    },
    "created_at": "2025-12-19T10:00:00+02:00",
    "updated_at": "2025-12-19T10:00:00+02:00"
}
```

### PatientResource (Admin view)
```json
{
    "id": 1,
    "name": "محمد أحمد",
    "phone": "+201019793768",
    "email": "patient@example.com",
    "date_of_birth": "1990-01-15",
    "age": 35,
    "gender": "male",
    "gender_label": "ذكر",
    "avatar_url": "https://...",
    "is_active": true,
    "created_at": "2025-12-19T10:00:00+02:00",
    "profile": { ... PatientProfileResource ... },
    "statistics": {
        "total_appointments": 15,
        "completed_appointments": 12,
        "cancelled_appointments": 2,
        "no_shows": 1,
        "last_visit": "2025-12-15"
    }
}
```

### PatientDashboardResource
```json
{
    "user": {
        "id": 1,
        "name": "محمد أحمد",
        "avatar_url": "https://..."
    },
    "profile_complete": true,
    "upcoming_appointments": [...],
    "statistics": {
        "total_appointments": 15,
        "upcoming_count": 2,
        "completed_count": 12,
        "next_appointment": {...}
    }
}
```

## 7. Files to Create

### Enum
- `app/Enums/BloodType.php`

### Migration
- `database/migrations/xxxx_create_patient_profiles_table.php`

### Model
- `app/Models/PatientProfile.php`

### Factory
- `database/factories/PatientProfileFactory.php`

### Requests
- `app/Http/Requests/CreatePatientProfileRequest.php`
- `app/Http/Requests/UpdatePatientProfileRequest.php`
- `app/Http/Requests/Admin/ListPatientsRequest.php`
- `app/Http/Requests/Admin/UpdatePatientNotesRequest.php`

### Resources
- `app/Http/Resources/PatientProfileResource.php`
- `app/Http/Resources/PatientResource.php`
- `app/Http/Resources/PatientDashboardResource.php`

### Controllers
- `app/Http/Controllers/Api/PatientController.php`
- `app/Http/Controllers/Api/Admin/PatientController.php`

### Tests
- `tests/Unit/Enums/BloodTypeTest.php`
- `tests/Unit/Models/PatientProfileTest.php`
- `tests/Feature/Api/PatientApiTest.php`
- `tests/Feature/Api/Admin/PatientApiTest.php`

## 8. Implementation Order

1. Create BloodType enum
2. Create patient_profiles migration
3. Create PatientProfile model
4. Update User model with relationships
5. Create PatientProfileFactory
6. Create Request classes
7. Create API Resources
8. Create Patient Controller (patient self-service)
9. Create Admin Patient Controller
10. Add routes to api.php
11. Write Unit Tests
12. Write Feature Tests
13. Run all tests
14. Commit and push

## 9. Business Rules

### Profile Management
1. Each patient can have only one profile
2. Profile is optional but recommended
3. Patient can update their own profile
4. Admin can update any patient's profile
5. Sensitive medical info visible only to staff

### Patient Listing (Admin)
1. Search by name, phone, or email
2. Filter by active/inactive status
3. Filter by blood type
4. Sort by name, date, or appointment count
5. Pagination support

### Dashboard
1. Show upcoming appointments
2. Show appointment statistics
3. Indicate profile completion status
