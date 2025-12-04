# Build stage
FROM php:8.4-fpm-alpine AS builder

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zlib-dev \
    libwebp-dev \
    oniguruma-dev \
    mysql-client
# Note: libsodium n'est pas nécessaire, sodium est inclus dans PHP 8.3+

# Configure GD with JPEG and FreeType support
RUN docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    gd \
    mbstring \
    opcache
# Note: sodium est inclus par défaut dans PHP 8.3+

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (with dev dependencies for fixtures)
RUN composer install --prefer-dist --no-scripts --no-autoloader

# Copy application code
COPY . .

# Generate autoloader and run scripts
RUN composer dump-autoload --optimize --classmap-authoritative

# Production stage
FROM php:8.4-fpm-alpine

# Install runtime dependencies only
RUN apk add --no-cache \
    libzip \
    icu-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    zlib \
    libwebp \
    mysql-client

# Install build dependencies for extensions
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zlib-dev \
    libwebp-dev \
    oniguruma-dev

# Configure GD with JPEG and FreeType support
RUN docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp

# Install PHP extensions (same as builder)
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    gd \
    opcache

# Remove build dependencies to reduce image size
RUN apk del .build-deps

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy application from builder
WORKDIR /app
COPY --from=builder /app /app

# Create var directories if they don't exist
RUN mkdir -p /app/var/cache /app/var/log

# Set permissions
RUN chown -R www-data:www-data /app/var

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
