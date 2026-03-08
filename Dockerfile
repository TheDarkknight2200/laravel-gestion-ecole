FROM php:8.2-apache

# Extensions PHP
RUN apt-get update && apt-get install -y \
    curl zip unzip git \
    && docker-php-ext-install pdo pdo_mysql mbstring

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App
WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

# Apache config
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD php artisan migrate --force && apache2-foreground
