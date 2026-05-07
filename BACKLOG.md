# Project Closeout Backlog — Wave-by-Wave Execution Plan

**Source:** `AUDIT.md` (2026-05-07)
**Branching:** Each wave on `closeout/0X-<wave-name>`. PRs to `main`. Never direct-push to `main`.
**Commit style:** Conventional Commits (`feat`, `fix`, `chore`, `docs`, `test`, `refactor`, `perf`).
**No AI attribution** anywhere in commits, PRs, branches, or tags (per closeout prompt).

---

## Wave 1 — Security & Deploy Safety 🔴

**Goal:** Make a `make setup` deploy safe to put in front of a real clinic. Close all known CVEs.
**Branch:** `closeout/01-security-deploy-safety`
**Total effort:** S (≈ half a day)

### Tasks

- [ ] **W1-T1 — `npm audit fix` and verify no breaking changes** (C-1)
  - Run `cd frontend && npm audit fix`. Re-run `npm audit` until 0 high/critical.
  - If `next-intl` requires major version bump → flag for user (🛑 major version).
  - Run `npm test` and `npm run build` to confirm nothing breaks.
  - **Acceptance:** `npm audit` shows 0 high/critical; tests pass; build succeeds.

- [ ] **W1-T2 — Gate `DemoSeeder` on non-production env** (C-2)
  - Edit `database/seeders/DatabaseSeeder.php` to call `DemoSeeder` only when `app()->environment(['local', 'testing'])` is true OR when `SEED_DEMO_DATA=true` env is set.
  - Edit `docker/entrypoint.sh:34` to either skip seeders in production or pass `--class=ProductionSeeder`.
  - Add a `ProductionSeeder` that runs only `AdminSeeder` + `ClinicSettingSeeder` + `ScheduleSeeder` (no demo data).
  - **Acceptance:** `APP_ENV=production php artisan db:seed --force` does not create demo patients; tests still pass.

- [ ] **W1-T3 — Remove default credentials echo from entrypoint** (C-3)
  - Delete the `Admin Credentials` echo block in `docker/entrypoint.sh:50-53`.
  - Replace with `echo "First-time setup: log in and change the admin password — see DEPLOY.md"`.
  - **Acceptance:** Container start logs do not contain plaintext credentials.

- [ ] **W1-T4 — Force password change on first admin login** (H-7)
  - Add a `must_change_password` boolean to `users` table (migration). Default `true` for the seeded admin.
  - On login, if `must_change_password` is true, return a flag in the auth response; frontend redirects to a forced-change-password screen before granting access to admin pages.
  - On change, clear the flag.
  - **Acceptance:** Fresh seed → login as admin → forced to change password → cannot access `/admin/*` until done.

- [ ] **W1-T5 — Remove default credentials from `README.md`** (H-7)
  - Replace the credentials table with: "After deployment, you'll be prompted to set the admin password on first login. See DEPLOY.md."
  - **Acceptance:** No `admin123` substring anywhere in `README.md`.

### Wave 1 Acceptance
- All Critical findings closed.
- `npm audit` clean (high/critical).
- Production deploy creates no demo data and no leaked credentials.
- Backend tests still 791+, frontend lint+test+build pass.

---

## Wave 2 — Core Sale-Blockers 🟠

**Goal:** A visitor lands → understands the product → can sign up → receives a working OTP. End-to-end sellable.
**Branch:** `closeout/02-sale-blockers`
**Total effort:** M+M (≈ 2 days)

### Tasks

- [ ] **W2-T1 — Build landing page at `/`** (C-4)
  - Replace `frontend/src/app/page.tsx` with a real landing:
    - Hero: clinic name (from settings API) + doctor's name + photo + tagline
    - Services / specialties list (from clinic settings or hardcoded list — ask user)
    - "Book Appointment" primary CTA → `/login` (or `/register` if not logged in)
    - "How it works" 3-step: Register → Choose slot → Confirm
    - Working hours snippet from public schedule API
    - Footer: contact, address (from clinic settings), language switcher
  - SSR-friendly. Indexable. Responsive. RTL-aware.
  - **Acceptance:** Visitor sees clinic info on first paint; "Book Now" works; Lighthouse SEO ≥ 90.

- [ ] **W2-T2 — Document SMS provider env in `.env.example`** (H-1)
  - Add to `.env.example`:
    ```
    # SMS Provider (for OTP and reminders). Default 'log' writes to storage/logs.
    # For production, set to 'twilio' or 'vonage' and fill credentials below.
    SMS_PROVIDER=log
    TWILIO_SID=
    TWILIO_AUTH_TOKEN=
    TWILIO_FROM=
    VONAGE_KEY=
    VONAGE_SECRET=
    VONAGE_FROM=
    ```
  - Add a **Production Setup** section in `README.md` linking to a new `DEPLOY.md` (placeholder for Wave 3).
  - **Acceptance:** Following `.env.example` produces a working OTP send (test with Twilio trial).

- [ ] **W2-T3 — Add EmailService and appointment confirmation email** (H-2)
  - Document `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` in `.env.example` with a sensible default.
  - Add `App\Notifications\AppointmentConfirmedMail` (Mailable). Wire to `AppointmentObserver` so confirmation triggers send.
  - Use Laravel queue (already on `database` driver).
  - Email template: clinic name + doctor + date/time + cancellation link + working hours.
  - Make sending optional via `EMAIL_NOTIFICATIONS_ENABLED=true` flag; default false for local.
  - **Acceptance:** Booking → confirm → email queued; queue worker sends; email contains correct details. Tests cover the notification.

- [ ] **W2-T4 — Add favicon, manifest, OG tags via Next 16 metadata API** (H-3)
  - Delete Next.js boilerplate SVGs in `frontend/public/`.
  - Add `frontend/src/app/icon.tsx` (or `favicon.ico` from clinic logo).
  - Add `frontend/src/app/manifest.ts` (PWA basics: name, short_name, theme_color, icons).
  - Add `frontend/src/app/opengraph-image.tsx` (auto-generated OG image with clinic name).
  - Update `app/layout.tsx` metadata: title template `%s | <clinic name>`, description from clinic settings, openGraph + twitter cards.
  - **Acceptance:** Browser tab shows real favicon; sharing on WhatsApp shows preview card; Lighthouse PWA section partially passes.

### Wave 2 Acceptance
- Visitor → landing page → register → OTP arrives via SMS → confirm booking → email arrives.
- Browser tab and social share previews look professional.

---

## Wave 3 — Production Deployment 🟠

**Goal:** A new doctor's IT person can self-host this in under 60 minutes following docs.
**Branch:** `closeout/03-production-deployment`
**Total effort:** M+M+M (≈ 2 days)

### Tasks

- [ ] **W3-T1 — Env-driven CSP in `next.config.ts`** (H-4)
  - Replace hardcoded `localhost:8000/9000` with values derived from `NEXT_PUBLIC_API_URL`.
  - Default development to permissive; production to strict.
  - **Acceptance:** `NEXT_PUBLIC_API_URL=https://api.example.com npm run build` produces a CSP that allows `https://api.example.com` and nothing else.

- [ ] **W3-T2 — Write `DEPLOY.md`** (H-5)
  - Sections: System requirements, Initial setup (Docker + non-Docker), Reverse proxy (nginx config snippet), SSL (Let's Encrypt), First-run admin setup, Email/SMS providers, Backups, Monitoring, Troubleshooting.
  - Include sample nginx config for frontend (reverse-proxy to Next standalone) and backend (php-fpm).
  - **Acceptance:** Person unfamiliar with the project can deploy following docs alone.

- [ ] **W3-T3 — First-run setup wizard for admin** (H-6)
  - After forced password change (W1-T4), if `clinic_settings` has empty `name`, redirect to `/admin/setup`.
  - Wizard steps: 1) Clinic info (name, phone, address, logo) → 2) Working hours (Schedule per day) → 3) Slot duration & cancellation policy → Done.
  - On completion, mark `setup_completed_at` in clinic_settings; subsequent admin logins skip wizard.
  - **Acceptance:** Fresh admin login → password change → setup wizard → admin dashboard. No way to skip.

- [ ] **W3-T4 — Add Sentry (or self-hosted equivalent) for backend + frontend** (M-3)
  - Backend: `sentry/sentry-laravel` package; configure DSN via env.
  - Frontend: `@sentry/nextjs`; configure DSN via env.
  - Both default to disabled if DSN is empty (no errors when not configured).
  - **Acceptance:** Forcing an error in dev with a real DSN produces an event in Sentry; without DSN, app doesn't crash.

- [ ] **W3-T5 — Backup script + docs** (M-4)
  - Add `scripts/backup.sh`: dumps MySQL + tarballs `storage/app/public` (medical attachments).
  - Add `scripts/restore.sh`.
  - Document in `DEPLOY.md` how to schedule via cron + how to restore.
  - **Acceptance:** Running `./scripts/backup.sh` produces a timestamped `.tar.gz` containing DB dump + uploads.

### Wave 3 Acceptance
- A new clinic can deploy, configure, and start booking patients in one session, following only `DEPLOY.md`.
- Errors in production are visible in Sentry.
- Backups can be taken and restored.

---

## Wave 4 — Market Parity 🟡

**Goal:** Match the minimum feature set of regional competitors so prospects don't reject on a feature comparison.
**Branch:** `closeout/04-market-parity`
**Total effort:** L+M+S+M (≈ 3-4 days)

### Tasks

- [ ] **W4-T1 — Paymob (Egyptian payment gateway) integration** (M-1) 🛑 *Decision needed*
  - **🛑 Ask user before starting:** Paymob requires merchant account (you may need to create one). Confirm: Paymob vs Fawry vs Stripe vs none?
  - If approved: Integrate Paymob `iframe` flow for booking deposits (e.g., 50 EGP deposit to confirm). Add `PaymentGateway` interface so we can swap providers.
  - Update `book/page.tsx` flow: select slot → pay deposit → confirmed (vs current pending).
  - **Acceptance:** Booking with deposit moves directly to `confirmed` status; webhook handles Paymob callback.

- [ ] **W4-T2 — 24h appointment reminder job** (M-2)
  - Add `App\Console\Commands\SendAppointmentReminders`. Wire in `routes/console.php` to run hourly.
  - Sends SMS + email (if enabled) to confirmed appointments 24h out and 1h out.
  - Idempotent — track `reminder_sent_at` on appointment.
  - **Acceptance:** Schedule a confirmed appointment for tomorrow → cron run → SMS + email sent; second run does not re-send.

- [ ] **W4-T3 — SEO basics** (M-5)
  - Add `frontend/src/app/robots.ts` and `frontend/src/app/sitemap.ts`.
  - Add JSON-LD `MedicalBusiness` schema to landing page (clinic name, address, phone, openingHours).
  - **Acceptance:** Google Rich Results Test passes for landing page.

- [ ] **W4-T4 — PWA basics** (M-6)
  - Add a service worker via `next-pwa` or manual installation.
  - Make app installable (manifest.ts already done in W2-T4).
  - Offline shell for `/login` and `/dashboard`.
  - **Acceptance:** Lighthouse PWA score > 80; "Install" prompt appears on Chrome mobile.

### Wave 4 Acceptance
- Patients can pay deposits online (if W4-T1 approved).
- Reminders go out automatically.
- Search engines can index the clinic.
- App can be installed as PWA on mobile.

---

## Wave 5 — Polish & Cleanup 🟢

**Goal:** Remove cruft, fix doc drift, tighten quality gates without strangling future development.
**Branch:** `closeout/05-polish`
**Total effort:** S+S (≈ 2-3 hours)

### Tasks

- [ ] **W5-T1 — Adjust coverage threshold** (M-8)
  - In `.github/workflows/ci.yml`, change `--min=100` to `--min=90` for general; keep 100% for critical-path classes (Auth, Payment, Appointment) via separate test config.
  - Document the policy in `CONTRIBUTING.md`.
  - **Acceptance:** New tests can land with 90% coverage on new code; critical-path 100% maintained.

- [ ] **W5-T2 — Delete Next.js boilerplate SVGs** (M-7)
  - `rm frontend/public/{file,globe,next,vercel,window}.svg`.
  - Verify no imports reference them.

- [ ] **W5-T3 — Sweep error responses for inconsistency** (L-4)
  - Grep all controllers for direct `response()->json` calls; replace with `ApiResponse::*` helpers where appropriate.

- [ ] **W5-T4 — Doc + style cleanup**
  - Fix README E2E count: 4 → 7 (L-1).
  - Standardize seeder password style (L-2): use `Hash::make()` everywhere or rely on cast everywhere — pick one.
  - Reconcile `X-Frame-Options` between Laravel `SecurityHeaders` (DENY) and `vercel.json` (SAMEORIGIN) (L-3).
  - Commit `composer.lock` if missing (L-5).

### Wave 5 Acceptance
- No dead files, no doc drift, no inconsistent error response formats.
- Quality gates are sustainable (CI passes on first try for typical features).

---

## Wave 6 — Test Critical Paths (always-on, lives across all waves)

**Goal:** Every wave's changes get tested. No "I'll test later."

For each wave's tasks:
- New backend code → matching test in `tests/Feature/Api/` or `tests/Unit/`.
- New frontend code → matching test in `frontend/src/__tests__/`.
- New user-facing flow → E2E spec in `frontend/e2e/`.
- New env var → documented in `.env.example` AND in `DEPLOY.md`.

---

## For Mahmoud (Non-Code) — Decisions Needed

These items are explicitly **out of scope** per the closeout prompt. Surfacing them here so the launch isn't blocked silently:

| ID | Decision needed | Why it matters | When |
|----|-----------------|----------------|------|
| D-1 | Confirm target market specifics: Egypt-only, MENA, or international? | Drives SMS provider (Twilio worldwide vs Vonage for MENA), payment gateway (Paymob/Fawry vs Stripe), pricing currency (EGP vs USD), and i18n priority. | Before Wave 4 |
| D-2 | Decide payment processor for online deposit | Required before W4-T1; Paymob/Fawry need merchant account creation by you. | Before Wave 4 |
| D-3 | Decide pricing model (SaaS subscription vs one-time license) | Affects whether we need billing infrastructure (Stripe Billing) or just a payment page. | Before any GTM work |
| D-4 | Provide clinic branding: doctor's name, photo, clinic logo, contact, address, working hours, services | Needed for landing page (W2-T1). I can stub with placeholders, but real content is your call. | Before/during Wave 2 |
| D-5 | Decide whether to keep current `--min=100` coverage gate or relax to `--min=90` | Affects sustainability of future contributions. | Wave 5 |
| D-6 | Confirm whether to add multi-tenancy in v2 or stay single-clinic-per-deploy | Major architectural decision that doesn't block this closeout, but affects how SaaS provisioning works. | Post-closeout |

---

## Wave Status Tracker

| Wave | Status | PR | Notes |
|------|--------|-----|-------|
| 1 — Security & Deploy Safety | ⏳ Pending approval | — | — |
| 2 — Core Sale-Blockers | ⏳ Pending approval | — | — |
| 3 — Production Deployment | ⏳ Pending approval | — | — |
| 4 — Market Parity | ⏳ Pending approval | — | Has 🛑 decision (W4-T1) |
| 5 — Polish & Cleanup | ⏳ Pending approval | — | — |
