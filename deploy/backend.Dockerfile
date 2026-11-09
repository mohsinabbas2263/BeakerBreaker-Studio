# Laravel 12 API and Filament admin, PHP 8.2 FPM. Built from the repository root:
#   docker compose -f deploy/docker-compose.yml build
# Target "app" serves three roles (app, queue, scheduler); the command differs.
# Target "web" is nginx with a fresh copy of public/ from the same build, so the
# admin panel's assets are never stale after a deploy.

FROM composer:2.8 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

FROM php:8.2-fpm-alpine AS app

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev $PHPIZE_DEPS \
 && docker-php-ext-install pdo_mysql intl zip opcache bcmath pcntl \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# Production PHP: no error display, OPcache on (NFR-01).
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'opcache.enable=1\nopcache.memory_consumption=192\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0\nexpose_php=Off\nmemory_limit=256M\n' \
    > "$PHP_INI_DIR/conf.d/zz-beaker.ini"

WORKDIR /var/www/html
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY backend/ ./

# filament:upgrade publishes the admin panel's CSS and JS into public/.
RUN composer dump-autoload --optimize --no-dev \
 && php artisan package:discover --ansi \
 && php artisan filament:upgrade \
 && chown -R www-data:www-data storage bootstrap/cache

USER www-data
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
