FROM php:8.3-fpm

# 1. Install system dependencies (added libicu-dev for Symfony intl support)
RUN apt-get update && apt-get install -y \
    nginx \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions (including intl)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli gd intl

# 3. Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Set working directory and copy files
WORKDIR /app
COPY . .

# 5. Set production environment
ENV APP_ENV=prod
ENV APP_DEBUG=0

# 6. Install project dependencies
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev

# 7. Set proper permissions
RUN chown -R www-data:www-data /app/var /app/public && \
    chmod -R 775 /app/var

# 8. Copy Nginx config
COPY config/nginx/railway.conf /etc/nginx/nginx.conf

# 9. Copy and set up entrypoint
COPY src/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Create required directories for Nginx and PHP-FPM
RUN mkdir -p /var/log/nginx /var/run/php-fpm

# 11. Dynamic Health check using Railway's $PORT variable instead of hardcoded 8080
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:$PORT/ || exit 1

# 12. Run your startup entrypoint script
CMD ["/entrypoint.sh"]