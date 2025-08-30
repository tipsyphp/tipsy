
# Use Alpine 3.3 which had PHP 5.6.x in 2014-2015 timeframe
FROM alpine:3.3

# Install PHP 5.6 and required extensions (2014-2015 era packages)
RUN apk add --no-cache \
    php \
    php-fpm \
    php-pdo \
    php-pdo_mysql \
    php-mysqli \
    php-json \
    php-ctype \
    php-curl \
    php-openssl \
    php-phar \
    php-zip \
    curl \
    nginx

# Configure PHP for 2014-2015 era settings (PHP 5.6.x)
RUN echo "error_reporting = E_ALL ^ (E_NOTICE | E_STRICT)" >> /etc/php/php.ini && \
    echo "display_errors = On" >> /etc/php/php.ini && \
    echo "log_errors = On" >> /etc/php/php.ini && \
    echo "date.timezone = UTC" >> /etc/php/php.ini

# Install Composer 1.0.0-alpha8 (available in 2014, true to the era)
RUN curl -sS https://getcomposer.org/installer | php -- --version=1.0.0-alpha8 && \
    mv composer.phar /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer

# Configure nginx to point to web directory following pulpoloco pattern
RUN rm -f /etc/nginx/conf.d/default.conf
COPY app-hello/src/nginx.conf /etc/nginx/conf.d/default.conf

# Replace the entire nginx.conf with a clean version that includes conf.d
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

# Configure PHP-FPM for Alpine PHP package
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9000/' /etc/php/php-fpm.conf && \
    sed -i 's/;listen = 127.0.0.1:9000/listen = 127.0.0.1:9000/' /etc/php/php-fpm.conf

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