# ---- build stage (composer + npm build) ----
FROM php:8.2-fpm-alpine AS build

# System deps
RUN apk add --no-cache \
    git curl unzip icu-dev oniguruma-dev libzip-dev zip \
    nodejs npm

# PHP extensions
RUN docker-php-ext-install \
    pdo pdo_mysql intl mbstring zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only composer files first (better caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy app
COPY . .

# Build frontend (Vite)
RUN npm ci && npm run build

# Optimize Laravel caches (safe even without DB)
RUN php artisan config:cache || true \
 && php artisan route:cache || true \
 && php artisan view:cache  || true


# ---- runtime stage (nginx + php-fpm + supervisor) ----
FROM php:8.2-fpm-alpine AS runtime

RUN apk add --no-cache \
    nginx supervisor bash icu-dev oniguruma-dev libzip-dev zip \
 && docker-php-ext-install \
    pdo pdo_mysql intl mbstring zip opcache

WORKDIR /var/www/html

# Copy app from build stage
COPY --from=build /var/www/html /var/www/html

# Nginx + Supervisor config
RUN mkdir -p /run/nginx /var/log/supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Render will send traffic to $PORT
EXPOSE 10000

# Start supervisor (nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]