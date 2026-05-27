#!/bin/sh
set -e

echo "🚀 Preparing Symfony Application..."

# Force the context into production mode
export APP_ENV=prod

echo "🧹 Clearing and warming up Symfony cache for production..."
php bin/console cache:clear --no-debug
php bin/console cache:warmup --no-debug

echo "📥 Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "🟢 Starting PHP-FPM..."
php-fpm -D

# FIX: This explicitly target-replaces the ${PORT} placeholder inside Nginx's main directory
echo "🔧 Dynamically injecting Railway port: $PORT..."
sed -i "s/\${PORT}/$PORT/g" /etc/nginx/nginx.conf

echo "⚡ Starting Nginx Server..."
exec nginx -g "daemon off;"