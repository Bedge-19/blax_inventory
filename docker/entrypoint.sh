#!/bin/sh
set -e

# Default PORT to 8080 if not injected by Railway
export PORT="${PORT:-8080}"

echo "Starting container on PORT ${PORT}..."

# Substitute only ${PORT} into the Nginx config template to protect Nginx variables ($uri, $document_root, etc.)
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Ensure writable directory permissions for www-data
if [ -d "/var/www/html/writable" ]; then
    chown -R www-data:www-data /var/www/html/writable
    chmod -R 775 /var/www/html/writable
fi

# Start PHP-FPM in the background
echo "Starting PHP-FPM daemon..."
php-fpm -D

# Start Nginx in the foreground as PID 1 to keep container alive
echo "Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
