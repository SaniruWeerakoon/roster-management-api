# ---------- Base Image with All Required Extensions ----------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    git unzip curl bash \
    icu-dev oniguruma-dev libzip-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql gd \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------- Install Dependencies ----------
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

# ---------- Copy Application ----------
COPY . .

# 4) Run composer scripts now that artisan exists
RUN composer dump-autoload --optimize \
 && php artisan package:discover --ansi

# Permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
