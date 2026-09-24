FROM php:8.3-fpm

# Install Nginx, gettext-base (for envsubst), system build tools, and PHP extension dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    gettext-base \
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
    && docker-php-ext-install intl mbstring pdo_mysql mysqli zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer binary from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Install PHP production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set correct permissions for CodeIgniter 4 writable storage directory
RUN chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

# Copy Nginx configuration template and entrypoint script
COPY docker/nginx.conf.template /etc/nginx/conf.d/default.conf.template
RUN chmod +x /var/www/html/docker/entrypoint.sh

EXPOSE 8080

CMD ["/var/www/html/docker/entrypoint.sh"]
