# Features Inventory

> A reference matrix of every feature the system ships. Sales conversations
> often go "does it do X?" — answer here first, then go to the codebase
> only if a specific edge case comes up.

Legend:
- ✅ Working in production
- 🟡 Working but with caveats (see notes)
- 🔵 Built but optional / off-by-default
- ⛔ Not in scope (intentionally not built)

---

## Authentication & Identity

| Feature | Status | Notes |
|---------|:------:|-------|
| Phone-based login (Egyptian numbers `01[0125]xxxxxxxx`) | ✅ | OTP not required for login if password is set; OTP is the recovery path |
| Password login with bcrypt (12 rounds) | ✅ | `BCRYPT_ROUNDS=12` in `.env.example` |
| OTP for password reset | ✅ | 6-digit, 10-minute expiry, 5-attempt lockout |
| Forced password change on first login | ✅ | `must_change_password` flag, seed admin gets this true |
| Role-based authorization (admin / secretary / patient) | ✅ | Laravel Policies per model |
| Soft delete on user account (with restore path) | ✅ | `softDeletes` on users table |
| Session refresh / token rotation | ✅ | `/api/auth/refresh` rotates Sanctum tokens |
| Phone verification gate before booking | ✅ | `phone_verified_at` required to book |
| Avatar upload | ✅ | Max 2 MB, MIME type validation, no extension spoof |

## Appointment Booking

| Feature | Status | Notes |
|---------|:------:|-------|
| Patient self-booking via calendar | ✅ | Picks future dates, sees available slots |
| Slot generator (per weekly schedule, slot duration setting) | ✅ | Service `SlotGeneratorService` honours breaks + vacations |
| Vacation/holiday blocking | ✅ | Admin enters vacations, slots auto-suppressed |
| Slot-availability check before submit | ✅ | Race-safe via DB constraint + check endpoint |
| Appointment status workflow (`pending → confirmed → completed`) | ✅ | + `cancelled`, `no_show` terminal states |
| Patient-initiated cancellation (with reason) | ✅ | Admin can override |
| Admin-initiated cancellation / no-show | ✅ | Notifies patient |
| Notes field on appointment (admin-side) | ✅ | Edit-only by admin/secretary |
| Patient sees upcoming appointments | ✅ | Sorted by date, status badges |
| Bulk filtering for admin (date / patient / status) | ✅ | `/admin/appointments` with URL-state filters |

## Patient & Medical Records

| Feature | Status | Notes |
|---------|:------:|-------|
| Patient profile (DOB, gender, address, emergency contact) | ✅ | Created on first booking or on demand |
| Blood type, allergies, chronic diseases, insurance | ✅ | `patient_profiles` table |
| Medical record per visit | ✅ | Vitals, diagnosis, treatment, follow-up |
| Vitals tracking (BP, HR, temp, weight, height) | ✅ | Numeric fields with validation ranges |
| Attachments (images / PDFs) on a record | ✅ | Stored on `storage/app/public/medical-attachments` |
| File preview + download (auth-checked) | ✅ | Cannot view another patient's attachment |
| Follow-up scheduling reminder | ✅ | `follow_ups_due` admin endpoint surfaces overdue |
| Soft delete + restore on medical records | ✅ | Audit-friendly |

## Prescriptions

| Feature | Status | Notes |
|---------|:------:|-------|
| Digital prescription with multiple medication items | ✅ | Each item: name, dosage, frequency, duration, instructions |
| Auto-generated prescription number (race-safe) | ✅ | Fixed in audit round 3 — atomic counter |
| PDF generation (Arabic + Latin glyphs) | ✅ | mpdf used for Arabic shaping |
| PDF download by patient and admin | ✅ | Authorization checks before each |
| Mark as dispensed (pharmacy use case) | ✅ | Tracks `dispensed_at`, `dispensed_by` |
| Filter by patient / dispensed status | ✅ | Admin list page |
| Expiry-date warning | 🟡 | Field exists; UI surfaces expired in red badge but no auto-notification |

## Payments

| Feature | Status | Notes |
|---------|:------:|-------|
| Multiple payment methods (Cash / Card / Insurance) | ✅ | Enum `PaymentMethod` |
| Discount per payment | ✅ | Cannot exceed amount |
| Payment status workflow (`pending → paid → refunded`) | ✅ | Refund records the original payment |
| Optional `appointment_id` (so out-of-clinic payments work) | ✅ | Migration made the FK nullable |
| Insurance claim tracking | 🔵 | Field captured but no insurance-portal integration ships by default |
| PDF invoice export | ✅ | mpdf, includes discount line |

## Reports

| Feature | Status | Notes |
|---------|:------:|-------|
| Revenue report (range, group-by week/month) | ✅ | PDF export available |
| Appointments report (by date / status / patient) | ✅ | PDF export available |
| Patients report (registration trend, totals) | ✅ | PDF export available |
| Per-patient statistics (totals, no-shows, revenue) | ✅ | Surfaces on patient detail page |

## Dashboards

| Feature | Status | Notes |
|---------|:------:|-------|
| Admin dashboard with KPIs (today / week / month) | ✅ | Cached 5 min, auto-refresh on focus |
| Patient dashboard (upcoming, records, prescriptions) | ✅ | Lightweight, single endpoint roundtrip |
| Recent activity feed | ✅ | Last 20 events, role-filtered |
| Weekly / monthly chart data | ✅ | Stacked bar for status mix |

## Notifications

| Feature | Status | Notes |
|---------|:------:|-------|
| In-app notification center | ✅ | Bell icon, unread count, mark all read |
| Appointment confirmation notification | ✅ | Sent when admin confirms |
| Cancellation notification (to patient / admin) | ✅ | Both directions |
| Payment received notification | ✅ | To patient on `paid` transition |
| Prescription ready notification | ✅ | When admin dispenses |
| Appointment reminder (T-24h SMS / push) | 🔵 | Job scheduled; needs SMS provider configured |
| Email notifications | 🔵 | Off by default; flip `EMAIL_NOTIFICATIONS_ENABLED=true` |

## Localization & UX

| Feature | Status | Notes |
|---------|:------:|-------|
| Arabic + English | ✅ | Full UI translation via `next-intl`; backend honours `Accept-Language` |
| RTL layout | ✅ | Tailwind RTL utilities + careful icon flipping |
| Dark mode | ✅ | `next-themes`; persists per user |
| Cairo font for Arabic + Latin | ✅ | Google Fonts, self-hosted in production |
| Mobile-responsive (320 px → 1920 px) | ✅ | Tested on real devices + Playwright projects |
| PWA installable | ✅ | manifest + icons (32, 192, 512) |

## Admin Tools

| Feature | Status | Notes |
|---------|:------:|-------|
| Weekly schedule editor (per day + break time) | ✅ | Live preview of generated slots |
| Vacation entries with reason | ✅ | Range-based, suppresses slots |
| Clinic settings (name, doctor, specialty, contact, services) | ✅ | Drives the public landing page |
| Logo upload | ✅ | Used in PDFs and the header |
| Setup banner on first login (forces required fields) | ✅ | Component `SetupBanner.tsx` |

## Security & Operations

| Feature | Status | Notes |
|---------|:------:|-------|
| OWASP Top 10 audit + fixes | ✅ | Documented findings closed in audit rounds |
| Rate limiting on auth endpoints | ✅ | 5 req/min on `/auth/*` |
| Brute-force protection on OTP | ✅ | 5 attempts → 30-minute lockout |
| HttpOnly auth cookies | ✅ | Sanctum default; `Secure` flag in production |
| HTTPS-only enforcement (HSTS) | ✅ | Header `Strict-Transport-Security` |
| Content Security Policy | 🟡 | `script-src` includes `'unsafe-inline'` to support Next 16 hydration; nonce-based CSP planned |
| X-Frame-Options / Referrer-Policy / Permissions-Policy | ✅ | All set in `next.config.ts` |
| Audit log (request_id correlation) | ✅ | JSON logs with `request_id` header |
| Daily backup cron + off-site sync template | ✅ | `scripts/backup.sh` ships |
| Restore script with integrity check | ✅ | `scripts/restore.sh` refuses mismatched archives |
| Health endpoint `/api/health` | ✅ | For uptime monitors |

## Intentionally Out of Scope

| Feature | Why not |
|---------|---------|
| Multi-tenant SaaS isolation | Target customer is single-clinic; multi-branch is Enterprise-tier scope |
| Real-time chat between patient and clinic | Out of medical scope; clinics handle escalation via phone |
| Insurance portal integrations (Egypt Care, Globemed) | Enterprise add-on, ≈ 2–4 weeks integration each |
| Telemedicine / video consults | Different product; recommend customer use Zoom + book a virtual slot |
| Online card payments (Paymob, Fawry) | Per-clinic concern; we can integrate per Enterprise contract |
| EMR (full medical record at the level of hospitals) | Different product class; this is a clinic system, not a hospital system |
