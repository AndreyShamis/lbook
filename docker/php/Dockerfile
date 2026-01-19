FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl zip pdo pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*
RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install xdebug && docker-php-ext-enable xdebug


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
