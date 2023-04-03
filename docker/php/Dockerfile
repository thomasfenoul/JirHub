FROM php:8-fpm-bullseye

RUN apt-get update && apt-get install -y \
    git \
    libicu-dev \
    libzip-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure \
    intl

RUN docker-php-ext-install \
    zip \
    intl

RUN echo "Install composer globally" \
   && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN groupadd -g 1000 php && \
    useradd --create-home --shell /bin/bash -u 1000 -g 1000 php \
    && mkdir /var/www/html -p && chown -R 1000:1000 /var/www

USER php

WORKDIR /var/www/html
