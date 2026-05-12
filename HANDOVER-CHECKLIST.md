# Handover Checklist — قائمة التسليم النهائية

> هذه قائمة التسليم بين فريق التطوير والعميل عند go-live. لازم كل بند يكون
> ✅ checked + موقّع من الطرفين قبل إصدار الفاتورة النهائية واحتساب بداية
> فترة الـ warranty.

**Project**: Clinic Booking System
**Client**: ___________________________
**Delivery date**: ____ / ____ / ______
**Tier**: ☐ Basic / ☐ Pro / ☐ Enterprise
**Warranty starts**: ____ / ____ / ______ (delivery + 1 day)
**Warranty ends**: ____ / ____ / ______ (delivery + 3/6/12 months per tier)

---

## 1. Infrastructure

- [ ] VPS / server provisioned with recommended specs (2 vCPU / 4 GB RAM / 80 GB SSD min for Pro tier)
- [ ] Operating system updated to latest stable (Ubuntu 22.04 / 24.04)
- [ ] Firewall (`ufw`) enabled with ports 22 / 80 / 443 only
- [ ] Fail2ban configured for SSH (3 failures = 1h ban)
- [ ] SSH access via key only (password authentication disabled)
- [ ] Non-root sudo user created for ops; root login disabled
- [ ] Timezone set correctly (typically `Africa/Cairo`)
- [ ] Swap configured (matching RAM size, swappiness=10)

## 2. Domain + TLS

- [ ] Domain registered and DNS pointing to server (A record propagated)
- [ ] `clinic.your-domain` (frontend) + `api.your-domain` (backend) records configured
- [ ] Let's Encrypt certs issued for both subdomains
- [ ] Certbot auto-renew tested (`certbot renew --dry-run` succeeds)
- [ ] HSTS header verified in response
- [ ] HTTP → HTTPS redirect tested

## 3. Backend (Laravel)

- [ ] PHP 8.2+ installed with required extensions (mbstring, dom, fileinfo, mysql, pdo_mysql, bcmath, gd, zip, intl)
- [ ] Composer installed and `composer install --no-dev --optimize-autoloader` ran successfully
- [ ] `.env` configured with production values (NOT `.env.example` defaults)
- [ ] `APP_DEBUG=false` confirmed
- [ ] `APP_KEY` generated
- [ ] `APP_URL` matches actual public URL
- [ ] Database connection works (`php artisan migrate:status`)
- [ ] All migrations ran successfully (`php artisan migrate --force`)
- [ ] Storage symlink created (`php artisan storage:link`)
- [ ] File permissions correct (`storage/` and `bootstrap/cache/` writable by www-data)
- [ ] Queue worker running as systemd service (verified with `systemctl status`)
- [ ] Scheduler running via crontab (`* * * * * php artisan schedule:run`)
- [ ] Config cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)

## 4. Frontend (Next.js)

- [ ] Node.js 20+ installed
- [ ] `npm ci` ran without errors
- [ ] `NEXT_PUBLIC_API_URL` configured to production backend URL
- [ ] `NEXT_PUBLIC_APP_URL` configured to production frontend URL
- [ ] Production build succeeded (`npm run build`)
- [ ] Started as systemd service (or running on Vercel/equivalent)
- [ ] Nginx reverse-proxying to Next.js process
- [ ] PWA manifest accessible at `/manifest.webmanifest`
- [ ] All icon sizes (32, 192, 512) served correctly
- [ ] Robots.txt + sitemap.xml accessible

## 5. Database

- [ ] MySQL 8.0+ running (or PostgreSQL if customer prefers — same Laravel support)
- [ ] DB charset is `utf8mb4` (Arabic support)
- [ ] DB user has only required privileges (no SUPER, no FILE)
- [ ] Connection limited to localhost or VPC only (no public exposure)
- [ ] Demo data **NOT** seeded in production (`SEED_DEMO_DATA=false` confirmed)
- [ ] Admin user created with phone `01000000000` and forced password change on first login
- [ ] Backup cron installed and tested:
  - [ ] First backup created successfully
  - [ ] Backup includes both DB + storage uploads
  - [ ] Backup archive integrity verified
  - [ ] Off-site copy (S3 / Spaces) configured

## 6. Email + SMS

- [ ] SMS provider configured (Vonage / Twilio) — NOT `log` driver
- [ ] Test OTP sent and received by admin phone
- [ ] Email provider configured (Postmark / SES / Mailgun)
- [ ] Test email sent and received
- [ ] `MAIL_FROM_ADDRESS` matches authorized sender
- [ ] SPF / DKIM / DMARC records added to DNS (for deliverability)
- [ ] Queue worker confirmed sending emails (not just queueing)

## 7. Security

- [ ] `npm audit` ran and shows 0 high/critical
- [ ] `composer audit` ran and shows 0 advisories
- [ ] `.env` file permissions `chmod 600` and owned by www-data
- [ ] `.git` directory not accessible via Nginx (returns 404 or 403)
- [ ] Database backups encrypted at rest (filesystem or disk-level)
- [ ] Server admin credentials stored in customer's password manager (not in plain text email)
- [ ] Rate limiting verified on `/api/auth/*` endpoints (test with curl)
- [ ] Default `admin123` password CHANGED to customer-chosen password
- [ ] No development-only routes exposed (test `/_ignition`, `/telescope` return 404)

## 8. Clinic-specific customization

- [ ] Clinic name set in `clinic_settings`
- [ ] Doctor name + specialization + bio set
- [ ] Clinic logo uploaded (or default removed if empty)
- [ ] Clinic phone + email + address set
- [ ] Services list populated
- [ ] Weekly schedule configured (working days + hours + break time)
- [ ] Initial vacation entries added (if any holidays in next 30 days)
- [ ] Tagline + about_text written (for landing page)
- [ ] Hero image uploaded (1920×1080 recommended)
- [ ] First test appointment booked end-to-end successfully

## 9. Monitoring + Operations

- [ ] Uptime monitor configured (Better Uptime / UptimeRobot)
  - [ ] Frontend URL monitored
  - [ ] Backend `/api/health` endpoint monitored
- [ ] Email alerts go to customer's ops email
- [ ] Log aggregation set up (or `tail -f` access documented for the first month)
- [ ] `request_id` correlation tested (find request in logs given an X-Request-ID)
- [ ] `php artisan queue:work` failures alerting works

## 10. Training delivered

- [ ] Doctor training session completed (60 min, recorded)
  - [ ] Dashboard overview
  - [ ] Today's appointments management
  - [ ] Patient management
  - [ ] Medical records + prescriptions creation
  - [ ] Reports + PDF exports
  - [ ] Clinic settings
- [ ] Secretary training session completed (30 min, recorded)
  - [ ] Appointment management
  - [ ] Patient registration on behalf
  - [ ] Payment recording
  - [ ] Schedule management
  - [ ] Vacation entries
- [ ] Patient onboarding doc / video shared (customer can forward to first patients)
- [ ] Common-issues quick-reference shared (one-pager PDF)

## 11. Documentation delivered

- [ ] `README.md` access provided
- [ ] `DEPLOY.md` technical guide provided
- [ ] `DEPLOYMENT.md` (this scenario) provided
- [ ] `API.md` provided (if customer's dev team will integrate)
- [ ] `SUPPORT-PLANS.md` agreed and signed
- [ ] `HANDOVER-CHECKLIST.md` (this doc) signed
- [ ] Repository access granted to customer (if Scenario A) — read-only or full per agreement
- [ ] Demo video / screencast archived in customer's drive

## 12. Legal + Commercial

- [ ] Master Service Agreement (MSA) signed
- [ ] Data Processing Agreement (DPA) signed (if EU/UK customer)
- [ ] Payment received (or first installment per agreement)
- [ ] Next invoice schedule confirmed (monthly support / annual renewal)
- [ ] Escalation contacts on file (customer side + our side)

## 13. Post-handover go/no-go test

24 hours after handover, run this checklist:

- [ ] Admin logs in successfully (no forced password change loop)
- [ ] Secretary logs in successfully
- [ ] A patient registers via the public site
- [ ] Patient receives OTP via SMS
- [ ] Patient books an appointment for a future slot
- [ ] Admin sees appointment in dashboard
- [ ] Admin confirms appointment
- [ ] Patient receives confirmation notification
- [ ] Backup ran overnight (verify timestamp in `BACKUP_DIR`)
- [ ] Uptime monitor shows green for last 24h
- [ ] No error in `storage/logs/laravel.log` of level >= warning that wasn't seen during testing

---

## التوقيعات

**ممثل العميل**
- الاسم: ___________________________
- المنصب: ___________________________
- التوقيع: ___________________________
- التاريخ: ____ / ____ / ______

**ممثل فريق التطوير**
- الاسم: Mahmoud Hamdy
- المنصب: Lead Developer / Founder, MWM Software Solutions
- التوقيع: ___________________________
- التاريخ: ____ / ____ / ______

---

## ملاحظات

أي بنود ما اتعملش (مش relevant للـ scenario، أو مؤجلة بطلب العميل) لازم
تتكتب هنا بسبب وتاريخ مقترح للإغلاق:

| البند | السبب | تاريخ مقترح للإغلاق |
|------|-------|---------------------|
|       |       |                     |
|       |       |                     |
|       |       |                     |
