# Specify the versions for Alpine, Node, and Composer
ARG ALPINE_VERSION=3.15 # version with python2
ARG NODE_VERSION=9.0.0-alpine
ARG COMPOSER_VERSION=2.2.25

# Stage 1: Node setup
FROM node:${NODE_VERSION} AS node

# Stage 2: Gulp setup
FROM alpine:${ALPINE_VERSION} AS gulp
COPY --from=node /usr/local/bin/node /usr/local/bin/
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
RUN ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
RUN apk add python2 make g++ bash vim
RUN mkdir -p /app
WORKDIR /app

# Stage 3: Composer installer setup
FROM composer:2.2.25 AS composer-installer

# Stage 4: PHP setup
FROM php:8.2-apache AS php
COPY --from=composer-installer /usr/bin/composer /usr/bin/composer
COPY . /var/www/html
RUN apt-get update && \
    apt-get install -y \
    libpng-dev \
    libzip-dev
RUN docker-php-ext-install \
    zip gd
RUN /usr/bin/composer install --no-dev --no-interaction

# Stage 5: Gulp builder with installed PHP dependencies
FROM gulp AS gulp-builder
COPY --from=php /var/www/html /app/
RUN npm --no-bin-link install
RUN node_modules/gulp/bin/gulp.js

# Stage 6: Production setup
FROM php AS production
RUN docker-php-ext-install \
    pdo pdo_mysql

RUN apt install -y \
    hunspell \
    hunspell-hu && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=gulp-builder /app/public/build /var/www/html/public/build
COPY --from=gulp-builder /app/public/css /var/www/html/public/css
COPY --from=gulp-builder /app/public/js /var/www/html/public/js

RUN mkdir -p bootstrap/cache && chown -R www-data:www-data bootstrap/cache
RUN chown -R www-data:www-data .

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN a2enmod rewrite headers
COPY docker/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint

# Stage 7: Development setup
FROM production AS development
RUN apt-get update && \
    apt-get install -y \
    libonig-dev \
    git \
    curl wget \
    libxml2-dev \
    unzip \
    vim \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
