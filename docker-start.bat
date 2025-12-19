@echo off
echo ========================================
echo  Clinic Booking System - Docker Setup
echo ========================================
echo.

REM Check if Docker is running
docker info > nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker is not running. Please start Docker Desktop first.
    pause
    exit /b 1
)

REM Check if .env exists
if not exist .env (
    echo Creating .env file from template...
    copy .env.example .env
)

REM Check if port 8000 is available, if not use 8001
set APP_PORT=8000
netstat -ano | findstr ":8000" > nul
if not errorlevel 1 (
    echo Port 8000 is in use, trying 8001...
    set APP_PORT=8001
    netstat -ano | findstr ":8001" > nul
    if not errorlevel 1 (
        echo Port 8001 is also in use, trying 8002...
        set APP_PORT=8002
    )
)

echo Using port: %APP_PORT%
echo.

REM Update .env with port
powershell -Command "(Get-Content .env) -replace 'APP_PORT=\d+', 'APP_PORT=%APP_PORT%' | Set-Content .env"

echo Building and starting containers...
docker-compose up -d --build

if errorlevel 1 (
    echo [ERROR] Failed to start containers.
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo  Application: http://localhost:%APP_PORT%
echo  API:         http://localhost:%APP_PORT%/api
echo  Health:      http://localhost:%APP_PORT%/api/health
echo.
echo  Admin Login:
echo  Phone: 01000000000
echo  Password: admin123
echo ========================================
echo.
pause
