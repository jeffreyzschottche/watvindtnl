FROM composer:2 AS composer_binary

FROM php:8.3-cli-alpine AS composer_build
WORKDIR /app

RUN apk add --no-cache \
        git \
        unzip \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        sqlite-dev \
        postgresql-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && rm -rf /var/cache/apk/*

COPY --from=composer_binary /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-alpine AS frontend_build
WORKDIR /app

COPY package.json ./
RUN npm install

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        sqlite-dev \
        postgresql-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
    && rm -rf /var/cache/apk/*

COPY . .
COPY --from=composer_build /app/vendor ./vendor
COPY --from=composer_build /app/bootstrap/cache ./bootstrap/cache
COPY --from=frontend_build /app/public/build ./public/build
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx \
    && mkdir -p storage/app/public \
    && touch database/database.sqlite \
    && chown -R www-data:www-data \
        bootstrap/cache \
        database \
        public \
        storage \
    && find storage bootstrap/cache -type d -exec chmod 775 {} \; \
    && find storage bootstrap/cache -type f -exec chmod 664 {} \;

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APP_URL=http://localhost
ENV LOG_CHANNEL=stderr
ENV SESSION_DRIVER=file
ENV CACHE_STORE=file
ENV QUEUE_CONNECTION=sync
ENV FILESYSTEM_DISK=public

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
