FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    zip \
    unzip \
    libpq \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install PHP dependencies (no dev, optimized for production)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Prepare Laravel environment
RUN cp .env.example .env && php artisan key:generate

# Expose port
EXPOSE 8000

# Start Laravel built-in server
CMD php artisan serve --host=0.0.0.0 --port=8000
