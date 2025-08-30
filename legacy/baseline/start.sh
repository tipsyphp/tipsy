#!/bin/sh

# Create directories if they don't exist
mkdir -p /run/nginx
mkdir -p /run/php5
mkdir -p /var/log/nginx
mkdir -p /var/log/php5

# Go to the app directory and install dependencies
cd /var/www/html
composer install --no-dev --optimize-autoloader --ignore-platform-reqs 2>/dev/null || echo "Using existing vendor directory"

# Set permissions
chown -R nobody:nobody /var/www/html
chmod -R 755 /var/www/html

# Start PHP-FPM in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"