FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite
RUN mkdir -p /var/www/html

WORKDIR /var/www/html