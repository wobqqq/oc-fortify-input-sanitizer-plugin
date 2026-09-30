FROM php:8.4-cli-alpine

RUN apk add --no-cache git unzip icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers icu-dev libzip-dev \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && docker-php-ext-install intl zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer/cache

RUN mkdir -p /tmp/composer/cache && chmod -R 0777 /tmp/composer

WORKDIR /app
