FROM php:8.2-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install PHP deps
COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-interaction --prefer-dist

COPY . .

RUN composer run-script auto-scripts --no-interaction 2>/dev/null || true

RUN mkdir -p /var/www/var/cache /var/www/var/log \
    && chown -R www-data:www-data /var/www/var /var/www/public
