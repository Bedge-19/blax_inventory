FROM php:8.3-apache

# System deps + PHP extensions this app needs
# - intl, mbstring: required by CodeIgniter 4 core
# - mysqli, pdo_mysql: database.default.DBDriver = MySQLi in .env.example
# - gd, curl, zip: needed by cloudinary/cloudinary_php for image uploads
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install intl mbstring pdo_mysql mysqli zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix Apache MPM conflict: ensure only mpm_prefork is enabled with mod_php
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Enable Apache rewrite (CI4 needs this for its .htaccess routing)
RUN a2enmod rewrite

# CI4's entry point is public/index.php, not the project root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (CI4 relies on this for pretty URLs)
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# CI4 needs writable/ to actually be writable by the web server
RUN chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

# Railway injects $PORT at container start; Apache must listen on it
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf
RUN sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
