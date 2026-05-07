# Coverage Audit — 2026-05-07

**Last measured CI coverage:** 83.7% (Wave 1 PR #1 run, before threshold relaxation)
**Current floor:** `--min=80` enforced in `.github/workflows/ci.yml`
**Target after this audit:** raise floor to `--min=90` in two follow-up waves

This document inventories where the gap lives. The 16-percentage-point gap
between aspirational 100% and reality 84% is concentrated in five areas;
addressing them in order makes a 90% floor sustainable.

---

## Methodology

Static inventory of `app/` against `tests/` based on:
1. **Direct reference**: any test file mentioning the class name (case-sensitive grep).
2. **Behavioral coverage**: a Feature test exercising the class's public surface (controller routes, observer triggers, etc.) — these don't show up in (1) but do count.

> A real coverage report from `php artisan test --coverage --min=90` would
> give per-line numbers. This static analysis identifies likely gaps for
> a follow-up CI run to confirm.

---

## Inventory snapshot

| Layer | Count | With dedicated test class | With behavioral coverage | Untested |
|-------|------:|--------------------------:|-------------------------:|---------:|
| Controllers (`app/Http/Controllers/Api/**`) | 17 | 0 | 17 (via 25 Feature tests) | 0 |
| Models (`app/Models/`) | 12 | 11 | 12 | 0 |
| Services (`app/Services/`) | 11 | **3** | 6 | **5** |
| Form Requests (`app/Http/Requests/**`) | ~25 | 0 | ~25 (via Feature tests' validation paths) | likely partial |
| Observers (`app/Observers/`) | 6 | 0 | mostly (fire on model save) | partial |
| Middleware (`app/Http/Middleware/`) | 8 | 1 (`AddRequestId`) | 4 (via Feature tests) | 3 |
| Exceptions (`app/Exceptions/`) | 3 | 0 | partial (thrown in services) | 0 outright but no negative-path coverage |
| Resources (`app/Http/Resources/**`) | ~15 | 0 | most (via Feature JSON assertions) | unclear |
| Notifications (`app/Notifications/`) | 5 | 0 | 4 (Confirmed + Reminder via Wave 2 + 4 tests; others by side-effect) | 1 (`PaymentReceived`) |

---

## 🔴 Priority 1 — Services with zero test coverage

These five services have **no test file referencing them by name** AND no obvious Feature-test path that exercises them. Coverage delta when fixed: estimated +6-8 percentage points.

### P1-1 — `PaymentService`
**Risk:** financial logic. Refunds, totals, discount handling — silently breaking these is the worst-case for a billing system.
**Action:** new `tests/Unit/Services/PaymentServiceTest.php` covering:
- `recordPayment()` happy path + invalid amount
- `markAsPaid()` state transition (only from `pending`)
- `refund()` only allowed from `paid`
- Discount calculation (`amount - discount = total`)
- Direct payments (no appointment) vs appointment-linked payments
**Estimate:** ~12 test cases, half a day.

### P1-2 — `SmsService`
**Risk:** silent OTP/reminder failures. The default `log` provider works, but the `vonage`/`twilio` branches are entirely untested.
**Action:** new `tests/Unit/Services/SmsServiceTest.php` using `Http::fake()`:
- `log` provider writes to log + returns true
- `vonage` provider calls the right URL and parses the success status
- `twilio` provider sends with basic auth
- `formatPhone()` adds `+2` prefix correctly
- `maskPhone()` returns expected format
**Estimate:** ~8 test cases, ~3 hours.

### P1-3 — `CacheInvalidationService`
**Risk:** stale dashboard / appointment data after writes. The observers depend on this.
**Action:** new `tests/Unit/Services/CacheInvalidationServiceTest.php`:
- Each `on*Changed()` method clears the right cache tags
- Cache tags reset after invalidation (assert `Cache::tags(...)->get()` returns null)
**Estimate:** ~6 test cases, 2 hours.

### P1-4 — `PrescriptionPdfService`
**Risk:** PDF generation breaks silently → patients can't download prescriptions.
**Action:** new `tests/Unit/Services/PrescriptionPdfServiceTest.php`:
- `generate()` returns a non-empty binary
- Output content-type is `application/pdf`
- Multiple prescription items render
**Estimate:** ~4 test cases, 2 hours. (DomPDF is slow in tests; mock if needed.)

### P1-5 — `LocalizationService`
**Risk:** lower — already exercised indirectly by `SetLocale` middleware. But no direct unit tests.
**Action:** add lightweight unit tests for the 3-4 public methods.
**Estimate:** ~5 test cases, 1 hour.

---

## 🟠 Priority 2 — Middleware without coverage

Five middleware have no test references and no obvious behavioral coverage. Most are exercised in passing by Feature tests (a `getJson` call goes through `AdminMiddleware`, etc.) but the negative paths aren't checked.

| Middleware | Risk | Suggested coverage |
|------------|------|---------------------|
| `AdminMiddleware` | Auth bypass if regressed | 2 cases: admin/secretary allowed, patient denied |
| `SecretaryMiddleware` | Same | 2 cases |
| `EnforcePasswordChange` | **Already covered by `ForcePasswordChangeTest` (Wave 1)** — false negative in static analysis (not referenced by class name) | Already done ✅ |
| `AuthenticateFromCookie` | Token injection | 3 cases: valid cookie, malformed cookie rejected, length cap enforced |
| `CacheApiResponse` | Stale data served | 2 cases: hit and miss |
| `SecurityHeaders` | Headers stripped | 1 case: assert all 7 expected headers present |
| `SetLocale` | i18n fails silently | 2 cases: header sets locale, invalid header falls back |

**Estimate for all:** ~15 cases, half a day. Coverage delta: +2-3 points.

---

## 🟡 Priority 3 — Behavioral coverage gaps in well-covered classes

Three services have Feature-test coverage but no isolated unit tests:

- `DashboardService` — exercised by `DashboardApiTest`. Edge cases (zero data, single-row periods) not isolated.
- `NotificationService` — exercised by `NotificationApiTest`. The new `sendAppointmentConfirmed` wiring (Wave 2) is covered; older methods less so.
- `ReportService` — exercised by `ReportApiTest`. PDF export branch likely thinly covered.

**Action:** add 3-4 cases each focused on edge inputs. Estimate: ~4 hours total. Coverage delta: +1-2 points.

---

## 🟢 Priority 4 — Resources, Form Requests, small classes

These contribute small amounts each (a few lines per class) but add up. Examples:

- `PublicClinicInfoResource` — covered behaviorally by `PublicClinicInfoTest` (Wave 2). Already adequate.
- New `AdminMiddleware`/`SecretaryMiddleware` — covered above in Priority 2.
- Exception classes — `BusinessLogicException`, `PaymentException`, `SlotNotAvailableException` are thrown in services but no test asserts on `getErrorCode()` / `getContext()` branches.

**Action:** opportunistic — add when touching adjacent code. No focused work needed.

---

## Suggested rollout

| Wave | Scope | Floor target |
|------|-------|-------------:|
| Coverage Wave 1 | P1-1 (PaymentService) + P1-2 (SmsService) | bump CI to `--min=87` |
| Coverage Wave 2 | P1-3, P1-4, P1-5 + Priority 2 middleware | bump CI to `--min=90` |
| Coverage Wave 3 | Priority 3 unit tests + opportunistic | bump CI to `--min=92` |

After each wave, **run the actual coverage report** (`php artisan test --coverage --min=87`) on a CI branch before raising the floor — static analysis here is a guide, not a measurement.

---

## What this audit does NOT do

- **Doesn't measure actual line coverage** — that needs `php artisan test --coverage` on a real PHP install. The 83.7% number from the Wave 1 CI run is the only ground truth we have.
- **Doesn't account for branch coverage** — a method may have 100% line coverage but only one of three branches tested.
- **Doesn't audit frontend** — Jest coverage is at "33 suites / 500 tests pass" but the `--coverage` report wasn't pulled. A separate audit is recommended once a frontend Coverage Wave is decided.

---

## Decision needed

Pick one of:
1. **Execute Coverage Wave 1 next** (~half a day): adds PaymentService + SmsService tests, validates +3 points actual lift, then commits the `--min=87` bump.
2. **Defer to a future closeout** — leave the `--min=80` floor as-is for now. Acceptable until a paying customer is onboarded.
3. **Frontend audit next** — measure frontend coverage first; the backend gap is known but the frontend isn't.
