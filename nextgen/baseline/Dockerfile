# Modern PHP 8.3 version of the legacy baseline
FROM php:8.3-fpm-alpine

# Install nginx and basic tools 
RUN apk add --no-cache \
    nginx \
    curl \
    bash

# Install PHP extensions needed for Tipsy
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache

# Copy nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Configure nginx main config
RUN echo 'user nobody;' > /etc/nginx/nginx.conf && \
    echo 'worker_processes 1;' >> /etc/nginx/nginx.conf && \
    echo 'events { worker_connections 1024; }' >> /etc/nginx/nginx.conf && \
    echo 'http {' >> /etc/nginx/nginx.conf && \
    echo '    include mime.types;' >> /etc/nginx/nginx.conf && \
    echo '    default_type application/octet-stream;' >> /etc/nginx/nginx.conf && \
    echo '    sendfile on;' >> /etc/nginx/nginx.conf && \
    echo '    keepalive_timeout 65;' >> /etc/nginx/nginx.conf && \
    echo '    include /etc/nginx/conf.d/*.conf;' >> /etc/nginx/nginx.conf && \
    echo '}' >> /etc/nginx/nginx.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY app-hello/ /var/www/html/

# Ensure proper permissions
RUN chown -R nobody:nobody /var/www/html && \
    chmod -R 755 /var/www/html

# Create startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
