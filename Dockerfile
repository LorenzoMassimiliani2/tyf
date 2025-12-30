# ---- build stage ----
FROM php:8.4-fpm-alpine AS build

RUN apk add --no-cache \
    git curl unzip icu-dev oniguruma-dev libzip-dev zip \
    nodejs npm postgresql-dev

RUN docker-php-ext-install \
    pdo pdo_mysql pdo_pgsql intl mbstring zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer first (cache-friendly)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy full app
COPY . .

# Frontend build (Vite)
RUN npm ci && npm run build

# Laravel optimizations
RUN php artisan config:clear \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache


# ---- runtime stage ----
FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache \
    nginx supervisor bash icu-dev oniguruma-dev libzip-dev zip postgresql-dev \
 && docker-php-ext-install \
    pdo pdo_mysql pdo_pgsql intl mbstring zip opcache

WORKDIR /var/www/html

COPY --from=build /var/www/html /var/www/html

RUN mkdir -p /run/nginx /var/log/supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "php artisan optimize:clear && php artisan serve --host=0.0.0.0 --port=10000"]