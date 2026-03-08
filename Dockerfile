FROM php:8.2-cli

# Dépendances système
RUN apt-get update && apt-get install -y \
    curl zip unzip git \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Code source
WORKDIR /app
COPY . .

# Dépendances Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissions
RUN chmod -R 775 storage bootstrap/cache

# Port exposé
EXPOSE 8000

# Démarrage
CMD php artisan config:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
