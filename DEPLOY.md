# Deployment Guide

This document covers deploying the Clinic Booking System to a production
server. The system targets **single-doctor clinics** and is designed to run
as one Docker stack (or a manual install) per clinic.

> If you're setting up locally for the first time, follow `README.md` —
> Docker quick start. This guide is for production.

---

## Contents

- [1. System Requirements](#1-system-requirements)
- [2. Initial Setup](#2-initial-setup)
  - [2.1 Docker (recommended)](#21-docker-recommended)
  - [2.2 Manual install](#22-manual-install)
- [3. Reverse Proxy + TLS](#3-reverse-proxy--tls)
- [4. First-run Admin Setup](#4-first-run-admin-setup)
- [5. SMS and Email Providers](#5-sms-and-email-providers)
- [6. Backups](#6-backups)
- [7. Monitoring & Logs](#7-monitoring--logs)
- [8. Upgrades](#8-upgrades)
- [9. Troubleshooting](#9-troubleshooting)

---

## 1. System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| OS        | Ubuntu 22.04 / Debian 12 | Ubuntu 24.04 |
| CPU       | 1 vCPU | 2 vCPU |
| RAM       | 1 GB   | 2 GB |
| Disk      | 20 GB  | 40 GB SSD (more if many attachments) |
| PHP       | 8.2    | 8.4 |
| Node      | 18     | 20 |
| MySQL     | 8.0    | 8.4 |
| HTTPS     | Required for production | — |

PHP extensions: `mbstring`, `dom`, `fileinfo`, `mysql`, `pdo_mysql`,
`bcmath`, `gd`, `zip`, `redis` (optional).

---

## 2. Initial Setup

### 2.1 Docker (recommended)

```bash
git clone https://github.com/mahmoodhamdi/Clinic-Booking-System.git
cd Clinic-Booking-System

cp .env.example .env
# Edit .env — at minimum set:
#   APP_URL=https://clinic.example.com
#   APP_KEY=                              # leave empty; entrypoint generates it
#   DB_CONNECTION=mysql
#   DB_DATABASE / DB_USERNAME / DB_PASSWORD
#   ADMIN_DEFAULT_PASSWORD=<pick-a-temp>  # required on first run
#   MAIL_FROM_ADDRESS=no-reply@your-domain
#   FRONTEND_URL=https://clinic.example.com
#   SEED_DEMO_DATA=false                  # CRITICAL — never true in prod

docker-compose up -d
```

The entrypoint (`docker/entrypoint.sh`) waits for MySQL, runs migrations,
creates a storage symlink, and starts php-fpm + nginx via supervisord. The
admin account `01000000000` is seeded with `must_change_password=true`, so
the first login forces you to set a real password.

**Frontend**: deploy `frontend/` separately (Vercel, or `Dockerfile` in
`frontend/`). Set `NEXT_PUBLIC_API_URL=https://api.your-domain/api` so the
browser calls the backend over HTTPS. Vercel users: add env vars in the
project dashboard.

### 2.2 Manual install

```bash
# System packages
sudo apt update
sudo apt install -y nginx mysql-server redis php8.2-fpm php8.2-{mbstring,dom,xml,fileinfo,mysql,bcmath,gd,zip,curl,intl} composer nodejs npm

# Backend
cd /var/www/clinic
composer install --no-dev --optimize-autoloader
cp .env.example .env
# edit .env (see env vars above)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
php artisan db:seed --class=ClinicSettingSeeder --force
php artisan db:seed --class=ScheduleSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

# File ownership
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Queue worker (under systemd or supervisor)
# Example: /etc/systemd/system/clinic-queue.service
#   [Service]
#   ExecStart=/usr/bin/php /var/www/clinic/artisan queue:work --tries=3
#   Restart=always
#   User=www-data

# Frontend (separate path or container)
cd frontend
npm ci
NEXT_PUBLIC_API_URL=https://api.your-domain/api npm run build
# serve via `npm start` behind nginx, or deploy to Vercel
```

---

## 3. Reverse Proxy + TLS

### 3.1 Backend nginx (Laravel/php-fpm)

`/etc/nginx/sites-available/api.your-domain`:

```nginx
server {
    listen 443 ssl http2;
    server_name api.your-domain;

    root /var/www/clinic/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/api.your-domain/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.your-domain/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.git { deny all; }
}

server {
    listen 80;
    server_name api.your-domain;
    return 301 https://$host$request_uri;
}
```

### 3.2 Frontend nginx (Next.js standalone)

```nginx
server {
    listen 443 ssl http2;
    server_name clinic.your-domain;

    ssl_certificate     /etc/letsencrypt/live/clinic.your-domain/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/clinic.your-domain/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 3.3 Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.your-domain -d clinic.your-domain
# Auto-renew is installed by the certbot apt package via systemd timer.
```

---

## 4. First-run Admin Setup

After the stack starts:

1. Browse to `https://clinic.your-domain` — you'll see the landing page
   with placeholder content (clinic name "العيادة" / doctor "الدكتور").
2. Log in at `/login` as `01000000000` with the password you set in
   `ADMIN_DEFAULT_PASSWORD`.
3. The system **forces** a password change before any admin page loads —
   set the doctor's permanent password.
4. After the change, fill out clinic details at `/admin/settings`:
   `clinic_name`, `doctor_name`, `specialization`, `tagline`, `phone`,
   `email`, `address`, plus optional `services`, `about_text`, `hero_image`,
   `logo`. The public landing page reads from these fields with a 5-minute
   revalidate, so updates are visible quickly.
5. Set the weekly schedule under `/admin/schedules` (defaults are
   Sun–Thu 09:00–17:00 with a 13:00–14:00 break; Fri/Sat closed).
6. Patients can now register and book.

---

## 5. SMS and Email Providers

### 5.1 SMS (OTP, future appointment reminders)

Default `SMS_PROVIDER=log` writes OTPs to `storage/logs/laravel.log` —
fine for staging, **never** for production. For Egypt/MENA, Vonage is
the recommended provider:

```env
SMS_PROVIDER=vonage
VONAGE_KEY=...
VONAGE_SECRET=...
VONAGE_FROM=ClinicEG
```

International fallback is Twilio (`SMS_PROVIDER=twilio` with `TWILIO_*`).

### 5.2 Email

Email notifications are off by default. To enable appointment confirmation
emails (Wave 2 feature):

```env
EMAIL_NOTIFICATIONS_ENABLED=true
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@your-domain
MAIL_FROM_NAME="${APP_NAME}"
```

Recommended providers: **Postmark** (transactional, great deliverability),
**Mailgun**, or **AWS SES**. Avoid Gmail SMTP for production — daily limits
will throttle once you have more than a handful of patients.

The queue worker must be running (`php artisan queue:work`) for emails to
actually send; the entrypoint starts one under supervisord in the Docker
setup.

---

## 6. Backups

The repo ships `scripts/backup.sh` and `scripts/restore.sh`. They handle
both MySQL and SQLite, plus the `storage/app/public` folder (avatars,
prescription PDFs, attachments).

### 6.1 Daily cron

```cron
# /etc/cron.d/clinic-backup
30 2 * * * www-data cd /var/www/clinic && BACKUP_DIR=/var/backups/clinic BACKUP_RETENTION=14 ./scripts/backup.sh >> /var/log/clinic-backup.log 2>&1
```

`BACKUP_RETENTION=14` keeps the last 14 archives (~2 weeks at daily); older
ones are pruned automatically.

### 6.2 Off-site copies

Whatever you do, **don't keep backups on the same host**. Cheap options:

- `aws s3 sync /var/backups/clinic s3://clinic-backups-eg/`
- `rclone copy /var/backups/clinic remote:clinic-backups`

Schedule the sync after the cron above (e.g. 03:00 daily).

### 6.3 Restore

```bash
./scripts/restore.sh /var/backups/clinic/clinic-backup-20260507-023000.tar.gz
```

The script refuses to run on archives missing the manifest, or archives
created against a different DB driver. Cache is cleared automatically after
import. **Test your restore path quarterly** on a staging copy — backups
you've never restored aren't backups, they're hope.

---

## 7. Monitoring & Logs

### 7.1 Log channel

Set `LOG_CHANNEL=json` (file) or `LOG_CHANNEL=json-stderr` (containerized)
in `.env`. The structured logger emits one JSON object per line:

```json
{"message":"Appointment booked successfully","level_name":"INFO","extra":{"request_id":"abc-123","user_id":42}}
```

`request_id` is also returned in the `X-Request-ID` response header — when
a patient reports an issue, ask for the screenshot's network tab `X-Request-ID`
and grep your logs for it.

### 7.2 Aggregation

Any of these work out of the box because lines are valid JSON:

- **Loki** (Grafana stack) — promtail tails the file, Grafana queries
- **ELK / OpenSearch** — Filebeat → Elasticsearch
- **Datadog / Better Stack / Logtail** — paid agents, easiest setup
- **Just `tail -f`** — fine for the first month while you find issues

### 7.3 Health endpoint

`GET /api/health` returns `{"status":"healthy", "timestamp":...}` — point
your uptime monitor (Better Uptime, UptimeRobot, etc.) at this URL with a
2–5 minute interval.

---

## 8. Upgrades

```bash
cd /var/www/clinic
git fetch origin
git checkout main
git pull --ff-only

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan queue:restart   # let the queue worker pick up new code

cd frontend
npm ci
NEXT_PUBLIC_API_URL=https://api.your-domain/api npm run build
# restart your Next.js process / redeploy to Vercel
```

For Docker: `docker-compose pull && docker-compose up -d`.

**Always take a backup before upgrading** (`./scripts/backup.sh`). Migrations
are forward-only; rolling back requires the restore script.

---

## 9. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Patients don't get OTPs | `SMS_PROVIDER=log` (default) | Set Vonage/Twilio creds; restart |
| Confirmation emails not sending | `EMAIL_NOTIFICATIONS_ENABLED=false` or queue worker not running | Flip flag + ensure `queue:work` is running under supervisord/systemd |
| 500 on every page after upgrade | Stale config cache | `php artisan config:clear && php artisan route:clear` |
| Patient avatars don't load | `NEXT_PUBLIC_API_URL` misconfigured (CSP blocks the wrong origin) | Rebuild frontend with the right env var |
| Migrations fail with "Specified key was too long" | MySQL < 8.0 / older charset | Upgrade MySQL or add `Schema::defaultStringLength(191)` in AppServiceProvider |
| Admin can't log in after deploy | Forgot `ADMIN_DEFAULT_PASSWORD` | Reset via `php artisan tinker`: `User::where('phone','01000000000')->first()->update(['password' => Hash::make('temp')])` then re-trigger forced change |
| Backup script fails with "permission denied" | `www-data` can't write `BACKUP_DIR` | `mkdir -p /var/backups/clinic && chown www-data:www-data /var/backups/clinic` |

For anything else, check `storage/logs/laravel.log` (or your aggregator)
filtering by the `request_id` from the affected request's `X-Request-ID`
response header.
