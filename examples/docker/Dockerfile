# =============================================================================
# Tipsy PHP — Production Docker Image
# Multi-stage build: composer install → slim runtime
#
# Build from the REPO ROOT:
#   docker build -f examples/docker/Dockerfile -t tipsy-app .
# =============================================================================

# --- Stage 1: Install dependencies ---
FROM composer:2 AS deps

WORKDIR /build

# Copy the framework source first (for the path repository)
COPY src/ /tipsy/src/
COPY composer.json /tipsy/composer.json

# Copy the example app
COPY examples/docker/composer.json ./

# Point the path repository at the copied framework source
# COMPOSER_MIRROR_PATH_REPOS=1 forces a copy instead of symlink (symlinks don't survive multi-stage builds)
RUN sed -i 's|"url": "../../"|"url": "/tipsy"|' composer.json \
    && COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# --- Stage 2: Runtime ---
FROM php:8.3-fpm-alpine AS runtime

# Install only what's needed — keep the image tiny
RUN apk add --no-cache nginx supervisor \
    && docker-php-ext-install pdo pdo_mysql opcache \
    && rm -rf /var/cache/apk/*

# OPcache tuning for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.jit=tracing'; \
    echo 'opcache.jit_buffer_size=64M'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# PHP production settings
RUN { \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/dev/stderr'; \
    echo 'memory_limit=128M'; \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=10M'; \
} > /usr/local/etc/php/conf.d/production.ini

# nginx config
COPY examples/docker/docker/nginx.conf /etc/nginx/http.d/default.conf

# supervisord to run nginx + php-fpm
COPY examples/docker/docker/supervisord.conf /etc/supervisord.conf

# Application code
WORKDIR /var/www/app
COPY --from=deps /build/vendor ./vendor
COPY examples/docker/web ./web
COPY examples/docker/views ./views

# Ensure nginx can read the files
RUN chown -R www-data:www-data /var/www/app

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
