# Docker Setup Guide

This guide explains how to run the Clinic Booking System using Docker.

## Prerequisites

- Docker Desktop installed ([Download](https://www.docker.com/products/docker-desktop))
- Docker Compose (included with Docker Desktop)

## Quick Start

### Option 1: Full Stack (MySQL + Redis)

```bash
# Clone the repository
git clone https://github.com/mahmoodhamdi/Clinic-Booking-System.git
cd Clinic-Booking-System

# Copy environment file
cp .env.example .env

# Build and start containers
docker-compose up -d --build

# Wait for services to initialize (about 30 seconds)
# Then access at http://localhost:8000
```

### Option 2: Lite Version (SQLite only)

```bash
# Build and start with SQLite
docker-compose -f docker-compose.lite.yml up -d --build

# Access at http://localhost:8000
```

## Port Configuration

If default ports are already in use, change them in `.env`:

```env
APP_PORT=8001        # Change from 8000
DB_PORT=3307         # Change from 3306
REDIS_PORT=6380      # Change from 6379
PHPMYADMIN_PORT=8081 # Change from 8080
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

## Available Services

| Service | URL | Description |
|---------|-----|-------------|
| API | http://localhost:8000/api | Main API |
| Health Check | http://localhost:8000/api/health | Health status |
| phpMyAdmin | http://localhost:8080 | Database GUI (with --profile tools) |

## Admin Credentials

After setup, login with:
- **Phone:** 01000000000
- **Password:** admin123

## Common Commands

### Using Make (recommended)

```bash
make help      # Show all commands
make setup     # First time setup
make up        # Start containers
make down      # Stop containers
make logs      # View logs
make shell     # Open container shell
make migrate   # Run migrations
make seed      # Run seeders
make fresh     # Fresh database + seed
make test      # Run tests
```

### Using Docker Compose

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f

# Rebuild after changes
docker-compose up -d --build

# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed

# Open shell
docker-compose exec app sh
```

### With phpMyAdmin

```bash
# Start with database GUI
docker-compose --profile tools up -d

# Access phpMyAdmin at http://localhost:8080
# Server: db
# Username: clinic_user
# Password: clinic_password
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| APP_PORT | 8000 | Application port |
| DB_PORT | 3306 | MySQL port |
| REDIS_PORT | 6379 | Redis port |
| PHPMYADMIN_PORT | 8080 | phpMyAdmin port |
| DB_DATABASE | clinic_booking | Database name |
| DB_USERNAME | clinic_user | Database user |
| DB_PASSWORD | clinic_password | Database password |

## Troubleshooting

### Port already in use

```bash
# Check what's using the port
# Windows:
netstat -ano | findstr :8000

# Linux/Mac:
lsof -i :8000

# Change port in .env and restart
```

### Database connection error

```bash
# Wait for MySQL to be ready
docker-compose logs db

# If stuck, restart
docker-compose down
docker-compose up -d
```

### Permission errors

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Fresh start

```bash
# Remove everything and start fresh
docker-compose down -v --rmi all
docker-compose up -d --build
```

## Production Deployment

For production, update `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use strong passwords
DB_PASSWORD=your_strong_password
DB_ROOT_PASSWORD=your_root_password
```

Then build with:
```bash
docker-compose -f docker-compose.yml up -d --build
```

## Architecture

```
┌─────────────────────────────────────────────────┐
│                   Docker Host                    │
├─────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────┐  │
│  │    App      │  │    MySQL    │  │  Redis  │  │
│  │  (PHP+Nginx)│  │     :3306   │  │  :6379  │  │
│  │    :80      │  │             │  │         │  │
│  └──────┬──────┘  └──────┬──────┘  └────┬────┘  │
│         │                │               │       │
│         └────────────────┴───────────────┘       │
│                    Network                        │
└─────────────────────────────────────────────────┘
         │
         ▼
    localhost:8000
```
