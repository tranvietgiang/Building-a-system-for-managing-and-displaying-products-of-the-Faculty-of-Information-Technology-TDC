FROM php:8.2-apache

# Cài thư viện cần thiết
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libzip-dev

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring zip

# Uploads can include multiple product images. Keep PHP limits above the UI cap.
RUN { \
    echo "upload_max_filesize=64M"; \
    echo "post_max_size=128M"; \
    echo "memory_limit=256M"; \
    echo "max_execution_time=300"; \
    echo "max_input_time=300"; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Bật rewrite Apache
RUN a2enmod rewrite

# Laravel chạy đúng /public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source
COPY . .

# Install Laravel packages
RUN composer install

# =========================
# PERMISSION (PHẦN ÔNG CẦN)
# =========================
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache || true

# Port Apache
EXPOSE 80

CMD ["apache2-foreground"]
