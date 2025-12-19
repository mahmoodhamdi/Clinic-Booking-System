#!/bin/sh
set -e

echo "========================================"
echo "  Clinic Booking System - Starting..."
echo "  (SQLite Lite Version)"
echo "========================================"

# Create SQLite database if not exists
if [ ! -f /var/www/database/database.sqlite ]; then
    echo "Creating SQLite database..."
    touch /var/www/database/database.sqlite
    chmod 664 /var/www/database/database.sqlite
fi

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
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
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
chmod 664 /var/www/database/database.sqlite

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
