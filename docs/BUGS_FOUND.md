# Bugs Found & Fixed

## Summary

| Severity | Found | Fixed | Open |
|----------|-------|-------|------|
| Critical | 2 | 2 | 0 |
| High | 7 | 7 | 0 |
| Medium | 6 | 6 | 0 |
| Low | 3 | 3 | 0 |
| **Total** | **18** | **18** | **0** |

---

## Critical Bugs

### #001 — SetLocale middleware not registered globally
- **Severity**: Critical
- **Component**: Backend (Middleware)
- **File**: `bootstrap/app.php`
- **Description**: The `SetLocale` middleware exists and is fully implemented but was never registered in the global API middleware stack. This means all API responses always use the default Arabic locale regardless of the `Accept-Language` header sent by the frontend.
- **Expected**: API responses respect the `Accept-Language` header (ar/en).
- **Actual**: All responses are in Arabic regardless of the header value.
- **Fix**: Added `\App\Http\Middleware\SetLocale::class` to the `api(append: [...])` middleware stack.
- **Status**: Fixed ✅

### #002 — PaymentPolicy::view() null pointer on direct payments
- **Severity**: Critical
- **Component**: Backend (Policy)
- **File**: `app/Policies/PaymentPolicy.php:30`
- **Description**: The `view` policy accesses `$payment->appointment->user_id` without null-checking. For direct payments (no appointment), `appointment_id` is null, causing a fatal error when a patient tries to view their payment.
- **Expected**: Policy returns true/false without crashing.
- **Actual**: Fatal error: accessing property on null.
- **Fix**: Added null check for `appointment_id`, using `patient_id` for direct payments and optional chaining for safety.
- **Status**: Fixed ✅

---

## High Severity Bugs

### #003 — Prescription number generation race condition
- **Severity**: High
- **Component**: Backend (Model)
- **File**: `app/Models/Prescription.php:126-141`
- **Description**: `generateNumber()` reads the last prescription and increments without any locking. Two concurrent prescription creations could read the same last number and generate duplicate prescription numbers.
- **Expected**: Unique prescription numbers under concurrent load.
- **Actual**: Potential duplicate numbers under high concurrency.
- **Fix**: Wrapped the logic in `DB::transaction()` with `lockForUpdate()` to ensure sequential number generation.
- **Status**: Fixed ✅

### #004 — Attachment::deleteFile() uses wrong storage disk
- **Severity**: High
- **Component**: Backend (Model)
- **File**: `app/Models/Attachment.php:119-126`
- **Description**: `deleteFile()` uses the default `local` disk but files are stored on the `public` disk. This means `Storage::exists()` always returns false, and attachments are never actually deleted from storage, creating orphaned files.
- **Expected**: File is deleted from public storage.
- **Actual**: File is never found or deleted (wrong disk).
- **Fix**: Changed to `Storage::disk('public')->exists()` and `Storage::disk('public')->delete()`.
- **Status**: Fixed ✅

### #005 — Frontend tests failing (10 tests in MedicalRecordDetail)
- **Severity**: High
- **Component**: Frontend (Tests)
- **File**: `frontend/src/__tests__/pages/MedicalRecordDetail.test.tsx`
- **Description**: The `next-intl` mock only mocked `useTranslations` but the component also uses `useLocale`, causing all 10 tests in the suite to fail.
- **Expected**: All tests pass.
- **Actual**: 10 tests fail with "useLocale is not a function" error.
- **Fix**: Added `useLocale: () => 'ar'` to the next-intl mock.
- **Status**: Fixed ✅

### #006 — Silent error handlers in admin mutations
- **Severity**: High
- **Component**: Frontend (Admin Pages)
- **Files**: `admin/medical-records`, `admin/payments`, `admin/prescriptions`, `admin/settings`, `admin/vacations`
- **Description**: Multiple admin pages use `onError: () => { toast.error(t('common.error')); }` which drops the actual error details. Users see a generic "error" message instead of the specific problem (e.g., "Patient not found", "Amount is required").
- **Expected**: Error messages from the API are shown to the user.
- **Actual**: Generic "error" message shown regardless of the actual error.
- **Fix**: Changed all 10 onError callbacks to use `getErrorMessage(error)` from the API client, with proper import added.
- **Status**: Fixed ✅

### #007 — RTL back-navigation arrow icons point wrong direction
- **Severity**: High
- **Component**: Frontend (Patient Pages)
- **Files**: `book/page.tsx`, `prescriptions/[id]/page.tsx`, `medical-records/[id]/page.tsx`
- **Description**: `ArrowRight` is used as a "Back" button icon. In Arabic RTL mode, the arrow points forward (right) instead of backward, confusing users. This is a visual bug affecting all Arabic-locale users.
- **Expected**: Back arrow points left in LTR, right in RTL.
- **Actual**: Arrow always points right regardless of locale.
- **Fix**: Added locale-aware `BackIcon` const that selects `ArrowLeft` or `ArrowRight` based on active locale.
- **Status**: Fixed ✅

### #008 — DashboardService uses hardcoded 'patient' string instead of UserRole enum
- **Severity**: High
- **Component**: Backend (Service)
- **File**: `app/Services/DashboardService.php` (lines 122, 166, 400)
- **Description**: Three instances of `User::where('role', 'patient')` use raw string instead of `UserRole::PATIENT` enum. If the enum value ever changes, these queries will silently break and return wrong statistics.
- **Expected**: Enum used consistently across codebase.
- **Actual**: Raw string used in 3 places.
- **Fix**: Added `use App\Enums\UserRole;` import and replaced all 3 occurrences with `UserRole::PATIENT`.
- **Status**: Fixed ✅

---

## Medium Severity Bugs

### #009 — AvatarImage receives empty string src causing 404 requests
- **Severity**: Medium
- **Component**: Frontend (Multiple)
- **Files**: `admin/patients`, `PatientLayout`, `AdminLayout`, `patient/profile`
- **Description**: `<AvatarImage src={patient.avatar || ''} />` passes an empty string as src, causing the browser to make a 404 HTTP request to the current origin for every user without an avatar.
- **Expected**: Radix AvatarImage skips image loading and shows fallback.
- **Actual**: 404 network request on every page load for users without avatars.
- **Fix**: Changed to `src={patient.avatar || undefined}` in all 5 instances.
- **Status**: Fixed ✅

### #010 — Missing aria-label on icon-only buttons
- **Severity**: Medium
- **Component**: Frontend (Accessibility)
- **Files**: `PatientLayout`, `AdminLayout`, `admin/vacations`
- **Description**: Several icon-only buttons (notifications bell, delete vacation) lack `aria-label`, making them inaccessible to screen readers.
- **Expected**: Screen readers announce button purpose.
- **Actual**: Buttons are announced as unlabeled.
- **Fix**: Added `aria-label={t('navigation.notifications')}` and `aria-label={t('common.delete')}` to respective buttons.
- **Status**: Fixed ✅

### #011 — Admin notification bell button is non-functional
- **Severity**: Medium
- **Component**: Frontend (AdminLayout)
- **File**: `components/layouts/AdminLayout.tsx:303`
- **Description**: The admin header bell button has no href or click handler — it's a dead UI element.
- **Expected**: Clicking the bell navigates to admin notifications.
- **Actual**: Clicking does nothing.
- **Fix**: Added `asChild` with `<Link href="/admin/notifications">` wrapper.
- **Status**: Fixed ✅

### #012 — debouncedSearch in queryKey but not passed to API
- **Severity**: Medium
- **Component**: Frontend (Admin Pages)
- **Files**: `admin/medical-records/page.tsx`, `admin/prescriptions/page.tsx`
- **Description**: `debouncedSearch` is included in the React Query `queryKey` but never passed to the `queryFn`. This causes React Query to refetch on every keystroke, but always fetches all records. The actual filtering happens client-side.
- **Expected**: Either pass search to API or remove from queryKey.
- **Actual**: Unnecessary API refetches on every search keystroke.
- **Fix**: Removed `debouncedSearch` from queryKey since filtering is done client-side.
- **Status**: Fixed ✅

### #013 — AddRequestId header injection risk
- **Severity**: Medium
- **Component**: Backend (Middleware)
- **File**: `app/Http/Middleware/AddRequestId.php:18`
- **Description**: The middleware blindly echoes the client-supplied `X-Request-ID` header into the response. A client could supply newline characters to perform response header injection.
- **Expected**: Only safe, sanitized values used in response headers.
- **Actual**: Arbitrary client-supplied values echoed in headers.
- **Fix**: Added regex validation (`/^[\w\-]{1,64}$/`) to accept only safe alphanumeric values with max 64 chars.
- **Status**: Fixed ✅

### #014 — AuthenticateFromCookie trusts any cookie value
- **Severity**: Medium
- **Component**: Backend (Middleware)
- **File**: `app/Http/Middleware/AuthenticateFromCookie.php:26-29`
- **Description**: The cookie value is injected directly into an HTTP Authorization header without any format validation. A malformed or oversized cookie could cause header issues.
- **Expected**: Only valid token formats are injected.
- **Actual**: Any string is injected regardless of content.
- **Fix**: Added format validation (`/^[a-zA-Z0-9|]+$/`) and max length check (512 chars).
- **Status**: Fixed ✅

---

## Low Severity Bugs

### #015 — ApiError name collision between types/index.ts and client.ts
- **Severity**: Low
- **Component**: Frontend (Types)
- **Files**: `types/index.ts:437`, `lib/api/client.ts:6`
- **Description**: Both files export a symbol named `ApiError` — one as an interface, one as a class. Any file importing from both would have a silent name collision.
- **Expected**: Unique names across modules.
- **Actual**: Potential name collision.
- **Fix**: Renamed the interface in `types/index.ts` to `ApiErrorResponse`.
- **Status**: Fixed ✅

### #016 — AuthResponse type declares token field that doesn't exist in response
- **Severity**: Low
- **Component**: Frontend (Types)
- **File**: `types/index.ts:42-49`
- **Description**: The `AuthResponse` interface declares a `token: string` field, but the auth uses Sanctum SPA cookie auth — the token is set as an HttpOnly cookie, not returned in the JSON body.
- **Expected**: Type matches actual API contract.
- **Actual**: Type has a `token` field that doesn't exist in the response.
- **Fix**: Changed `token: string` to `token?: string` with a clarifying comment.
- **Status**: Fixed ✅

### #017 — API exception responses ignore Accept-Language header
- **Severity**: High
- **Component**: Backend (Localization)
- **Files**: `bootstrap/app.php`, `lang/en.json`
- **Description**: Two issues: (1) Exception handlers in `bootstrap/app.php` use `__('Arabic text')` for translations, but no English translations existed in `lang/en.json` for these keys. (2) For 401/404 exceptions, the `SetLocale` middleware hasn't run yet when the exception handler renders the response, so locale is always the default (Arabic).
- **Expected**: Error messages respect `Accept-Language: en` header.
- **Actual**: All error messages always returned in Arabic.
- **Fix**: Added all 46 Arabic translation keys with English values to `lang/en.json`. Added locale detection directly in exception handlers in `bootstrap/app.php` to ensure locale is set before translating.
- **Status**: Fixed

### #018 — DashboardController unbounded limit parameter
- **Severity**: Low
- **Component**: Backend (Controller)
- **File**: `app/Http/Controllers/Api/Admin/DashboardController.php:60,69`
- **Description**: The `recentActivity` and `upcomingAppointments` endpoints accept `$request->limit` without validation or cap. A caller could pass `limit=100000` and force a massive query.
- **Expected**: Limit is capped at a reasonable maximum.
- **Actual**: Any value is accepted.
- **Fix**: Added `min((int) (...), 50)` cap on both endpoints.
- **Status**: Fixed ✅
