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
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP-FPM config for Docker
COPY docker.conf /usr/local/etc/php-fpm.d/docker.conf

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

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
