FROM php:8.3-fpm

# Install Nginx, gettext-base (for envsubst), system build tools, poppler-utils (pdfinfo), and PHP extension dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    gettext-base \
    poppler-utils \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl mbstring pdo_mysql mysqli zip gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer binary from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Install PHP production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set correct permissions for CodeIgniter 4 writable storage directory and uploads
RUN mkdir -p /var/www/html/writable/cache \
             /var/www/html/writable/logs \
             /var/www/html/writable/session \
             /var/www/html/writable/uploads \
             /var/www/html/writable/uploads/printing \
             /var/www/html/writable/uploads/business_permits \
             /var/www/html/writable/debugbar \
             /var/www/html/public/uploads/profiles \
             /var/www/html/public/uploads/product_images \
             /var/www/html/public/uploads/business_permits \
             /var/www/html/public/uploads/shop_logos \
             /var/www/html/public/uploads/cms \
             /var/www/html/public/uploads/printing \
             /var/www/html/public/uploads/printing_attachments \
    && chown -R www-data:www-data /var/www/html/writable /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/writable /var/www/html/public/uploads

# Copy Nginx configuration template and entrypoint script
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx.conf.template /etc/nginx/conf.d/default.conf.template

# Ensure entrypoint has LF line endings (safe from Windows CRLF) and executable permission
RUN sed -i 's/\r$//' /var/www/html/docker/entrypoint.sh \
    && chmod +x /var/www/html/docker/entrypoint.sh

EXPOSE 8080

CMD ["/var/www/html/docker/entrypoint.sh"]
