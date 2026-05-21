#!/bin/sh
set -e

# Create .env from .env.example if missing (container has no .env by default)
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Write environment variables passed via docker into .env so Artisan commands can read them
for var in APP_KEY APP_ENV APP_DEBUG APP_URL DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
    val=$(eval echo "\$$var")
    if [ -n "$val" ]; then
        sed -i "s|^${var}=.*|${var}=${val}|" /var/www/html/.env
    fi
done

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Create SQLite database file if not exists
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations
php artisan migrate --force

# Clear and cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create supervisor log directory
mkdir -p /var/log/supervisor

# Start services via supervisor
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
