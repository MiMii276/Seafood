#!/bin/sh
set -e

echo "🚀 Preparing Symfony Application..."

# Warm up the production cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Run Database Migrations automatically on deployment
echo "📥 Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "🟢 Starting PHP-FPM..."
php-fpm -D

echo "⚡ Starting Nginx Server..."
exec nginx -g "daemon off;"