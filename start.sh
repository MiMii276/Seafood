#!/bin/sh
echo "Starting php-fpm..."
php-fpm -D
echo "php-fpm started, starting nginx..."
nginx -g 'daemon off;'
echo "nginx exited with code True"
