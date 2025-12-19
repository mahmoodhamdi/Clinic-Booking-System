#!/bin/bash
set -e

echo "========================================"
echo "  Clinic Booking System - Starting..."
echo "========================================"

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=db;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done
echo "MySQL is ready!"

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache config
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Run seeders (they handle duplicates gracefully)
echo "Running seeders..."
php artisan db:seed --force

# Create storage link
echo "Creating storage link..."
php artisan storage:link 2>/dev/null || true

# Set permissions
echo "Setting permissions..."
chown -R www:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "========================================"
echo "  Application is ready!"
echo "  Access at: http://localhost:${APP_PORT:-8000}"
echo "========================================"
echo ""
echo "  Admin Credentials:"
echo "  Phone: 01000000000"
echo "  Password: admin123"
echo "========================================"

# Execute the main command
exec "$@"
