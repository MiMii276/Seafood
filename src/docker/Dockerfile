# Stage 1: Build the Symfony application
FROM php:8.3-fpm-alpine AS builder

# Install system dependencies and PHP extensions required by Symfony
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    libpng-dev \
    bash

RUN docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    gd \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install dependencies for production
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# ---------------------------------------------------

# Stage 2: Final Production Image
FROM php:8.3-fpm-alpine

# Install Nginx and runtime dependencies
RUN apk add --no-cache nginx supervisor bash

RUN docker-php-ext-install pdo_mysql opcache

# Copy custom Nginx configuration
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Set working directory and copy application from builder stage
WORKDIR /var/www/html
COPY --from=builder /app /var/www/html

# FIX: Ensure proper permissions using the built-in www-data user
RUN chown -R www-data:www-data /var/www/html/var && \
    chmod -R 775 /var/www/html/var

# Expose Railway's default dynamic port
EXPOSE 80

# Set the Entrypoint script
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]