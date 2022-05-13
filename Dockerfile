FROM php:8.1-fpm-buster

RUN apt-get update && apt-get install -y --no-install-recommends \
    git-core \
    libicu-dev \
    libzip-dev \
    unzip \
    zlib1g-dev \
    wget

RUN docker-php-ext-install \
    zip \
    intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN groupadd -g 1000 php && \
    useradd --create-home --shell /bin/bash -u 1000 -g 1000 php

USER php

WORKDIR /var/www/html
