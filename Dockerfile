FROM php:8.2-apache

# Install ekstensi & dependensi sistem
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install pdo_mysql mbstring bcmath gd

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Disable conflicting MPM modules and ensure only one is active
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Set Document Root ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Install dependencies composer
RUN composer install --no-dev --optimize-autoloader

# Atur hak akses folder storage & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
CMD ["apache2-foreground"]

