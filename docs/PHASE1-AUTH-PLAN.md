# Phase 1: Project Setup & Authentication - Implementation Plan

> **Status: COMPLETED**
> All tasks in this phase have been successfully implemented and tested.

## Overview
This phase covers the foundation of the Clinic Booking System including Laravel project setup, database configuration, User model with roles, and complete authentication system.

---

## 1. Project Setup

### 1.1 Create Laravel Project
```bash
composer create-project laravel/laravel . --prefer-dist
```

### 1.2 Required Packages
```bash
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require barryvdh/laravel-dompdf
composer require intervention/image
composer require propaganistas/laravel-phone

# Dev packages
composer require --dev phpunit/phpunit
composer require --dev mockery/mockery
composer require --dev laravel/pint
```

### 1.3 Environment Configuration
- Database: MySQL (clinic_booking)
- Queue: database
- Locale: ar (Arabic) with en fallback
- Sanctum for API authentication

---

## 2. Database Schema

### 2.1 Users Table Migration
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique()->nullable();
    $table->string('phone')->unique();
    $table->string('password');
    $table->enum('role', ['admin', 'secretary', 'patient'])->default('patient');
    $table->date('date_of_birth')->nullable();
    $table->enum('gender', ['male', 'female'])->nullable();
    $table->string('address')->nullable();
    $table->string('avatar')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('phone_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 3. User Model Design

### 3.1 User Model (app/Models/User.php)
- Implements SoftDeletes
- Has role attribute (admin, secretary, patient)
- Phone-based authentication (primary)
- Email optional
- Avatar support
- Active/Inactive status

### 3.2 Enums
- `app/Enums/UserRole.php` - admin, secretary, patient
- `app/Enums/Gender.php` - male, female

### 3.3 Model Methods
```php
// Role checks
public function isAdmin(): bool
public function isSecretary(): bool
public function isPatient(): bool

// Scopes
public function scopeActive($query)
public function scopePatients($query)
public function scopeStaff($query)
```

---

## 4. Authentication Flow

### 4.1 Registration Flow
1. Patient submits: name, phone, email (optional), password
2. Validate phone format (Egyptian: 01xxxxxxxxx)
3. Create user with role='patient'
4. Generate Sanctum token
5. Return user data + token

### 4.2 Login Flow
1. User submits: phone + password
2. Validate credentials
3. Check if user is active
4. Generate Sanctum token
5. Return user data + token

### 4.3 Logout Flow
1. Revoke current token
2. Return success message

### 4.4 Password Reset Flow
1. User requests reset with phone
2. Generate OTP (6 digits)
3. Send via SMS (log driver for dev)
4. User verifies OTP
5. User sets new password

### 4.5 Profile Management
1. Get current user profile
2. Update profile (name, email, date_of_birth, gender, address)
3. Change password (requires current password)
4. Upload avatar

---

## 5. API Endpoints

### 5.1 Public Endpoints (No Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/register | Register new patient |
| POST | /api/auth/login | Login |
| POST | /api/auth/forgot-password | Request password reset |
| POST | /api/auth/verify-otp | Verify OTP |
| POST | /api/auth/reset-password | Reset password with OTP |

### 5.2 Protected Endpoints (Auth Required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/logout | Logout |
| POST | /api/auth/refresh | Refresh token |
| GET | /api/auth/me | Get current user |
| PUT | /api/auth/profile | Update profile |
| POST | /api/auth/change-password | Change password |
| POST | /api/auth/avatar | Upload avatar |
| DELETE | /api/auth/account | Delete account |

---

## 6. Request Validation

### 6.1 RegisterRequest
```php
'name' => 'required|string|max:255',
'phone' => 'required|string|phone:EG|unique:users,phone',
'email' => 'nullable|email|unique:users,email',
'password' => 'required|string|min:8|confirmed',
```

### 6.2 LoginRequest
```php
'phone' => 'required|string',
'password' => 'required|string',
```

### 6.3 UpdateProfileRequest
```php
'name' => 'sometimes|string|max:255',
'email' => 'nullable|email|unique:users,email,' . auth()->id(),
'date_of_birth' => 'nullable|date|before:today',
'gender' => 'nullable|in:male,female',
'address' => 'nullable|string|max:500',
```

### 6.4 ChangePasswordRequest
```php
'current_password' => 'required|string|current_password',
'password' => 'required|string|min:8|confirmed',
```

---

## 7. API Response Format

### 7.1 Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### 7.2 Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

### 7.3 UserResource
```json
{
    "id": 1,
    "name": "Ahmed Mohamed",
    "email": "ahmed@example.com",
    "phone": "01012345678",
    "role": "patient",
    "date_of_birth": "1990-01-15",
    "gender": "male",
    "address": "Cairo, Egypt",
    "avatar": "http://localhost/storage/avatars/1.jpg",
    "is_active": true,
    "phone_verified_at": "2024-01-15T10:00:00Z",
    "created_at": "2024-01-15T10:00:00Z"
}
```

---

## 8. Blade Views

### 8.1 Layouts
- `layouts/guest.blade.php` - Auth pages layout

### 8.2 Auth Views
- `auth/login.blade.php` - Login form
- `auth/register.blade.php` - Registration form
- `auth/forgot-password.blade.php` - Request reset
- `auth/verify-otp.blade.php` - OTP verification
- `auth/reset-password.blade.php` - New password form

---

## 9. Middleware

### 9.1 AdminMiddleware
- Checks if user has admin role
- Returns 403 if not authorized

### 9.2 SecretaryMiddleware
- Checks if user has admin or secretary role
- Returns 403 if not authorized

### 9.3 EnsurePhoneVerified
- Checks if phone_verified_at is set
- Returns 403 if not verified (optional based on settings)

---

## 10. Testing Strategy

### 10.1 Unit Tests (tests/Unit/)

#### UserTest.php
- test_user_has_correct_fillable_attributes
- test_user_has_correct_hidden_attributes
- test_user_has_correct_casts
- test_is_admin_returns_true_for_admin_role
- test_is_admin_returns_false_for_non_admin_role
- test_is_patient_returns_true_for_patient_role
- test_scope_active_returns_only_active_users
- test_scope_patients_returns_only_patients
- test_user_can_be_soft_deleted

#### UserRoleEnumTest.php
- test_enum_has_correct_values
- test_enum_labels

#### GenderEnumTest.php
- test_enum_has_correct_values

### 10.2 Feature Tests (tests/Feature/Api/Auth/)

#### RegistrationTest.php
- test_user_can_register_with_valid_data
- test_registration_requires_name
- test_registration_requires_valid_phone
- test_registration_requires_unique_phone
- test_registration_requires_password_confirmation
- test_registration_fails_with_weak_password
- test_registered_user_receives_token

#### LoginTest.php
- test_user_can_login_with_valid_credentials
- test_login_fails_with_wrong_password
- test_login_fails_with_nonexistent_phone
- test_inactive_user_cannot_login
- test_login_returns_token

#### LogoutTest.php
- test_user_can_logout
- test_logout_invalidates_token
- test_unauthenticated_user_cannot_logout

#### ProfileTest.php
- test_user_can_get_own_profile
- test_user_can_update_profile
- test_user_can_change_password
- test_password_change_requires_current_password
- test_user_can_upload_avatar
- test_user_can_delete_account

#### PasswordResetTest.php
- test_user_can_request_password_reset
- test_password_reset_fails_for_nonexistent_phone
- test_user_can_verify_otp
- test_user_can_reset_password

---

## 11. Files to Create

### Controllers
- `app/Http/Controllers/Api/AuthController.php`

### Requests
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/UpdateProfileRequest.php`
- `app/Http/Requests/Auth/ChangePasswordRequest.php`
- `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- `app/Http/Requests/Auth/ResetPasswordRequest.php`

### Resources
- `app/Http/Resources/UserResource.php`

### Enums
- `app/Enums/UserRole.php`
- `app/Enums/Gender.php`

### Middleware
- `app/Http/Middleware/AdminMiddleware.php`
- `app/Http/Middleware/SecretaryMiddleware.php`

### Migrations
- `database/migrations/xxxx_create_users_table.php`
- `database/migrations/xxxx_create_password_reset_tokens_table.php`

### Factories
- `database/factories/UserFactory.php`

### Seeders
- `database/seeders/AdminSeeder.php`

### Tests
- `tests/Unit/Models/UserTest.php`
- `tests/Unit/Enums/UserRoleTest.php`
- `tests/Unit/Enums/GenderTest.php`
- `tests/Feature/Api/Auth/RegistrationTest.php`
- `tests/Feature/Api/Auth/LoginTest.php`
- `tests/Feature/Api/Auth/LogoutTest.php`
- `tests/Feature/Api/Auth/ProfileTest.php`
- `tests/Feature/Api/Auth/PasswordResetTest.php`

### Views
- `resources/views/layouts/guest.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/verify-otp.blade.php`
- `resources/views/auth/reset-password.blade.php`

---

## 12. Execution Order

1. Create Laravel project
2. Install all required packages
3. Configure .env file
4. Create Enums (UserRole, Gender)
5. Modify users migration
6. Create User model with methods
7. Create UserFactory
8. Create Form Requests
9. Create UserResource
10. Create AuthController
11. Define API routes
12. Create Middleware
13. Create Blade views
14. Write Unit Tests
15. Write Feature Tests
16. Run tests and verify 100% coverage
17. Create AdminSeeder
18. Update README and PROGRESS.md
19. Commit and push

---

## 13. Post-Implementation Verification

### Checklist
- [ ] All API endpoints working correctly
- [ ] Registration creates user and returns token
- [ ] Login validates credentials and returns token
- [ ] Logout invalidates token
- [ ] Profile can be viewed and updated
- [ ] Password can be changed
- [ ] Avatar can be uploaded
- [ ] Account can be deleted
- [ ] All unit tests passing
- [ ] All feature tests passing
- [ ] 100% code coverage achieved
- [ ] Blade views render correctly
- [ ] Admin seeder creates admin user

### Test Commands
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage --min=100

# Run specific test file
php artisan test --filter=RegistrationTest
```

---

## 14. Admin Seeder Data

```php
User::create([
    'name' => 'Dr. Admin',
    'email' => 'admin@clinic.com',
    'phone' => '01000000000',
    'password' => Hash::make('admin123'),
    'role' => 'admin',
    'is_active' => true,
    'phone_verified_at' => now(),
]);
```
