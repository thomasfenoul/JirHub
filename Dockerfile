FROM php:fpm-alpine

RUN apk update && apk add --no-cache \
    $PHPIZE_DEPS \
    zlib-dev \
    libzip-dev \
    icu-dev

RUN docker-php-ext-configure \
    intl

RUN docker-php-ext-install \
    zip \
    intl

RUN echo "Install composer globally" \
   && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

