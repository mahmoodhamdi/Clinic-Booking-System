#!/bin/bash

echo "========================================"
echo " Clinic Booking System - Docker Setup"
echo "========================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "[ERROR] Docker is not running. Please start Docker first."
    exit 1
fi

# Check if .env exists
if [ ! -f .env ]; then
    echo "Creating .env file from template..."
    cp .env.example .env
fi

# Find available port
find_available_port() {
    local port=$1
    while lsof -i :$port > /dev/null 2>&1 || netstat -tuln 2>/dev/null | grep -q ":$port "; do
        echo "Port $port is in use, trying $((port + 1))..."
        port=$((port + 1))
    done
    echo $port
}

APP_PORT=$(find_available_port 8000)
echo "Using port: $APP_PORT"
echo ""

# Update .env with port
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s/APP_PORT=.*/APP_PORT=$APP_PORT/" .env
else
    sed -i "s/APP_PORT=.*/APP_PORT=$APP_PORT/" .env
fi

# Export for docker-compose
export APP_PORT

echo "Building and starting containers..."
docker-compose up -d --build

if [ $? -ne 0 ]; then
    echo "[ERROR] Failed to start containers."
    exit 1
fi

echo ""
echo "========================================"
echo " Setup Complete!"
echo "========================================"
echo ""
echo " Application: http://localhost:$APP_PORT"
echo " API:         http://localhost:$APP_PORT/api"
echo " Health:      http://localhost:$APP_PORT/api/health"
echo ""
echo " Admin Login:"
echo " Phone: 01000000000"
echo " Password: admin123"
echo "========================================"
