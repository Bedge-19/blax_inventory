#!/bin/sh
set -e

# Default PORT to 8080 if not injected by Railway
export PORT="${PORT:-8080}"
export CI_ENVIRONMENT="${CI_ENVIRONMENT:-production}"

echo "Starting container on PORT ${PORT}..."

# Substitute only ${PORT} into the Nginx config template to protect Nginx variables ($uri, $document_root, etc.)
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Remove default Nginx site so only our port-configured site is active
rm -f /etc/nginx/sites-enabled/default

# Configure PHP-FPM to preserve environment variables (clear_env = no) and tune workers
if [ -d "/usr/local/etc/php-fpm.d" ]; then
    cat <<'EOF' > /usr/local/etc/php-fpm.d/zz-railway.conf
[www]
clear_env = no
catch_workers_output = yes
decorate_workers_output = no
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 1000
EOF
fi

# Configure PHP OPcache and production performance settings
if [ -d "/usr/local/etc/php/conf.d" ]; then
    cat <<'EOF' > /usr/local/etc/php/conf.d/zz-opcache.ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.fast_shutdown=1
realpath_cache_size=4096k
realpath_cache_ttl=600
EOF

    cat <<'EOF' > /usr/local/etc/php/conf.d/zz-uploads.ini
upload_max_filesize=50M
post_max_size=50M
memory_limit=512M
max_execution_time=180
max_input_time=180
default_socket_timeout=180
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

mkdir -p /var/www/html/public/uploads/profiles \
         /var/www/html/public/uploads/product_images \
         /var/www/html/public/uploads/business_permits \
         /var/www/html/public/uploads/shop_logos \
         /var/www/html/public/uploads/cms
chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 775 /var/www/html/public/uploads

# Start PHP-FPM in the background
echo "Starting PHP-FPM daemon..."
php-fpm -D

# Start Nginx in the foreground as PID 1 to keep container alive
echo "Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
