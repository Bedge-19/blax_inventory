#!/bin/sh
set -e

# Default PORT to 8080 if not injected by Railway
export PORT="${PORT:-8080}"

echo "Starting container on PORT ${PORT}..."

# Substitute only ${PORT} into the Nginx config template to protect Nginx variables ($uri, $document_root, etc.)
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Remove default Nginx site so only our port-configured site is active
rm -f /etc/nginx/sites-enabled/default

# Configure PHP-FPM to preserve environment variables (clear_env = no) and capture worker output
if [ -d "/usr/local/etc/php-fpm.d" ]; then
    cat <<'EOF' > /usr/local/etc/php-fpm.d/zz-railway.conf
[www]
clear_env = no
catch_workers_output = yes
decorate_workers_output = no
EOF
fi

# Ensure writable directories exist with correct permissions for www-data
mkdir -p /var/www/html/writable/cache \
         /var/www/html/writable/logs \
         /var/www/html/writable/session \
         /var/www/html/writable/uploads \
         /var/www/html/writable/debugbar
chown -R www-data:www-data /var/www/html/writable
chmod -R 775 /var/www/html/writable

# Start PHP-FPM in the background
echo "Starting PHP-FPM daemon..."
php-fpm -D

# Start Nginx in the foreground as PID 1 to keep container alive
echo "Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
