FROM php:8.2-cli

# Install dependensi sistem dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install pdo_mysql mbstring bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Install paket Composer
RUN composer install --no-dev --optimize-autoloader

# Set hak akses folder storage & cache Laravel
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Jalankan server bawaan PHP langsung menembak $PORT dari Railway
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}