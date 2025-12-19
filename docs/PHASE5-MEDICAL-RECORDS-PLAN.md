# Phase 5: Medical Records & Prescriptions - Implementation Plan

> **Status: COMPLETED**
> All tasks in this phase have been successfully implemented and tested.

## Overview
نظام السجلات الطبية والوصفات يتيح للطبيب توثيق الكشوفات وكتابة الوصفات الطبية وإرفاق الملفات.

## 1. Database Design

### medical_records table
```
id                    - bigint, primary key
appointment_id        - foreign key to appointments (unique, one record per appointment)
patient_id            - foreign key to users
diagnosis             - text (التشخيص)
symptoms              - text, nullable (الأعراض)
examination_notes     - text, nullable (ملاحظات الفحص)
treatment_plan        - text, nullable (خطة العلاج)
follow_up_date        - date, nullable (تاريخ المتابعة)
follow_up_notes       - text, nullable (ملاحظات المتابعة)
vital_signs           - json, nullable (blood_pressure, heart_rate, temperature, weight, height)
created_at            - timestamp
updated_at            - timestamp
```

### prescriptions table
```
id                    - bigint, primary key
medical_record_id     - foreign key to medical_records
prescription_number   - string, unique (رقم الوصفة)
notes                 - text, nullable (ملاحظات عامة)
valid_until           - date, nullable (صالحة حتى)
is_dispensed          - boolean, default false (تم صرفها)
dispensed_at          - timestamp, nullable
created_at            - timestamp
updated_at            - timestamp
```

### prescription_items table
```
id                    - bigint, primary key
prescription_id       - foreign key to prescriptions
medication_name       - string (اسم الدواء)
dosage                - string (الجرعة)
frequency             - string (عدد المرات)
duration              - string (المدة)
instructions          - text, nullable (تعليمات)
quantity              - integer, nullable (الكمية)
created_at            - timestamp
updated_at            - timestamp
```

### attachments table
```
id                    - bigint, primary key
attachable_type       - string (polymorphic)
attachable_id         - bigint (polymorphic)
file_name             - string
file_path             - string
file_type             - string (image, pdf, document)
file_size             - integer (bytes)
description           - string, nullable
uploaded_by           - foreign key to users
created_at            - timestamp
updated_at            - timestamp
```

## 2. Models

### MedicalRecord Model
- Relationships: belongsTo Appointment, belongsTo Patient (User), hasMany Prescriptions, morphMany Attachments
- Accessors: vital_signs_formatted, has_follow_up
- Casts: vital_signs as array

### Prescription Model
- Relationships: belongsTo MedicalRecord, hasMany PrescriptionItems
- Accessors: is_valid, items_count
- Methods: markAsDispensed(), generateNumber()

### PrescriptionItem Model
- Relationships: belongsTo Prescription
- Accessors: full_dosage_text

### Attachment Model
- Relationships: morphTo attachable, belongsTo uploader (User)
- Accessors: url, size_formatted

## 3. API Endpoints

### Medical Records APIs (Admin/Staff)
```
GET    /api/admin/medical-records                           - List all records (with filters)
GET    /api/admin/medical-records/{id}                      - Get record details
POST   /api/admin/appointments/{appointment}/medical-record - Create record for appointment
PUT    /api/admin/medical-records/{id}                      - Update record
DELETE /api/admin/medical-records/{id}                      - Delete record (soft)
GET    /api/admin/patients/{patient}/medical-records        - Get patient's records
```

### Prescriptions APIs (Admin/Staff)
```
GET    /api/admin/prescriptions                             - List all prescriptions
GET    /api/admin/prescriptions/{id}                        - Get prescription details
POST   /api/admin/medical-records/{record}/prescriptions    - Create prescription
PUT    /api/admin/prescriptions/{id}                        - Update prescription
DELETE /api/admin/prescriptions/{id}                        - Delete prescription
POST   /api/admin/prescriptions/{id}/dispense               - Mark as dispensed
GET    /api/admin/prescriptions/{id}/pdf                    - Download PDF
```

### Attachments APIs (Admin/Staff)
```
POST   /api/admin/medical-records/{record}/attachments      - Upload attachment
GET    /api/admin/attachments/{id}                          - Get attachment
DELETE /api/admin/attachments/{id}                          - Delete attachment
GET    /api/admin/attachments/{id}/download                 - Download file
```

### Patient Medical Records APIs (Patient view only)
```
GET    /api/patient/medical-records                         - Get my medical records
GET    /api/patient/medical-records/{id}                    - Get record details
GET    /api/patient/prescriptions                           - Get my prescriptions
GET    /api/patient/prescriptions/{id}                      - Get prescription details
GET    /api/patient/prescriptions/{id}/pdf                  - Download prescription PDF
```

## 4. Request Validation Classes

### CreateMedicalRecordRequest
- diagnosis: required, string, max:2000
- symptoms: nullable, string, max:1000
- examination_notes: nullable, string, max:2000
- treatment_plan: nullable, string, max:2000
- follow_up_date: nullable, date, after:today
- follow_up_notes: nullable, string, max:500
- vital_signs: nullable, array
- vital_signs.blood_pressure: nullable, string
- vital_signs.heart_rate: nullable, integer
- vital_signs.temperature: nullable, numeric
- vital_signs.weight: nullable, numeric
- vital_signs.height: nullable, numeric

### CreatePrescriptionRequest
- notes: nullable, string, max:500
- valid_until: nullable, date, after:today
- items: required, array, min:1
- items.*.medication_name: required, string, max:200
- items.*.dosage: required, string, max:100
- items.*.frequency: required, string, max:100
- items.*.duration: required, string, max:100
- items.*.instructions: nullable, string, max:500
- items.*.quantity: nullable, integer, min:1

### UploadAttachmentRequest
- file: required, file, max:10240 (10MB), mimes:jpg,jpeg,png,pdf,doc,docx
- description: nullable, string, max:200

## 5. API Resources

### MedicalRecordResource
```json
{
    "id": 1,
    "appointment": {...},
    "patient": {...},
    "diagnosis": "التشخيص",
    "symptoms": "الأعراض",
    "examination_notes": "ملاحظات الفحص",
    "treatment_plan": "خطة العلاج",
    "vital_signs": {
        "blood_pressure": "120/80",
        "heart_rate": 72,
        "temperature": 37.0,
        "weight": 75,
        "height": 175
    },
    "follow_up": {
        "date": "2025-12-30",
        "notes": "مراجعة بعد أسبوع"
    },
    "prescriptions": [...],
    "attachments": [...],
    "created_at": "2025-12-19T10:00:00+02:00"
}
```

### PrescriptionResource
```json
{
    "id": 1,
    "prescription_number": "RX-2025-0001",
    "medical_record_id": 1,
    "patient": {...},
    "notes": "ملاحظات",
    "valid_until": "2025-12-30",
    "is_valid": true,
    "is_dispensed": false,
    "items": [
        {
            "medication_name": "أموكسيسيلين",
            "dosage": "500mg",
            "frequency": "3 مرات يومياً",
            "duration": "7 أيام",
            "instructions": "بعد الأكل",
            "quantity": 21
        }
    ],
    "created_at": "2025-12-19T10:00:00+02:00"
}
```

## 6. PDF Prescription Template
- Clinic header (name, logo, address, phone)
- Patient info (name, phone, age)
- Prescription number and date
- Medications table
- Doctor signature area
- Valid until date

## 7. Files to Create

### Migrations
- `xxxx_create_medical_records_table.php`
- `xxxx_create_prescriptions_table.php`
- `xxxx_create_prescription_items_table.php`
- `xxxx_create_attachments_table.php`

### Models
- `app/Models/MedicalRecord.php`
- `app/Models/Prescription.php`
- `app/Models/PrescriptionItem.php`
- `app/Models/Attachment.php`

### Factories
- `database/factories/MedicalRecordFactory.php`
- `database/factories/PrescriptionFactory.php`
- `database/factories/PrescriptionItemFactory.php`
- `database/factories/AttachmentFactory.php`

### Requests
- `app/Http/Requests/Admin/CreateMedicalRecordRequest.php`
- `app/Http/Requests/Admin/UpdateMedicalRecordRequest.php`
- `app/Http/Requests/Admin/CreatePrescriptionRequest.php`
- `app/Http/Requests/Admin/UpdatePrescriptionRequest.php`
- `app/Http/Requests/Admin/UploadAttachmentRequest.php`

### Resources
- `app/Http/Resources/MedicalRecordResource.php`
- `app/Http/Resources/PrescriptionResource.php`
- `app/Http/Resources/PrescriptionItemResource.php`
- `app/Http/Resources/AttachmentResource.php`

### Controllers
- `app/Http/Controllers/Api/Admin/MedicalRecordController.php`
- `app/Http/Controllers/Api/Admin/PrescriptionController.php`
- `app/Http/Controllers/Api/Admin/AttachmentController.php`
- `app/Http/Controllers/Api/Patient/MedicalRecordController.php`

### Services
- `app/Services/PrescriptionPdfService.php`

### Tests
- `tests/Unit/Models/MedicalRecordTest.php`
- `tests/Unit/Models/PrescriptionTest.php`
- `tests/Feature/Api/Admin/MedicalRecordApiTest.php`
- `tests/Feature/Api/Admin/PrescriptionApiTest.php`
- `tests/Feature/Api/Patient/MedicalRecordApiTest.php`

## 8. Implementation Order

1. Create migrations for all tables
2. Create models with relationships
3. Create factories
4. Create request validation classes
5. Create API resources
6. Create Admin MedicalRecord controller
7. Create Admin Prescription controller
8. Create Admin Attachment controller
9. Create Patient MedicalRecord controller
10. Create PrescriptionPdfService
11. Add routes to api.php
12. Write Unit Tests
13. Write Feature Tests
14. Run all tests
15. Commit and push

## 9. Business Rules

### Medical Records
1. One medical record per completed appointment
2. Only staff can create/update records
3. Patients can view their own records (read-only)
4. Record can have multiple prescriptions
5. Record can have multiple attachments

### Prescriptions
1. Prescription belongs to a medical record
2. Auto-generate unique prescription number
3. Can be marked as dispensed
4. Has validity period
5. PDF can be generated and downloaded

### Attachments
1. Support images (jpg, png) and documents (pdf, doc)
2. Max file size: 10MB
3. Polymorphic - can attach to any model
4. Track who uploaded the file
