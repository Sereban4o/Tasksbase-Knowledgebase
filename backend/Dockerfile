FROM php:8.5-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip exif pcntl bcmath \
    && docker-php-ext-install gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Xdebug (optional, for debugging)
# RUN pecl install xdebug && docker-php-ext-enable xdebug

# Get latest Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP-FPM config for Docker
COPY docker.conf /usr/local/etc/php-fpm.d/docker.conf
COPY docker/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

# Create working directory
WORKDIR /var/www/html

# Create PHP-FPM socket directory
RUN mkdir -p /var/run/php && chown www-data:www-data /var/run/php

# Copy existing application directory contents
COPY . .

# Create storage symlink inside container
RUN ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-plugins --no-scripts --prefer-dist 2>/dev/null || true

# Ensure runtime directories exist (repo may omit empty storage dirs) and set permissions
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
