FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    git unzip libzip-dev icu-dev \
    && docker-php-ext-install zip intl pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN git config --global --add safe.directory /app

RUN addgroup -g 1000 appuser && \
    adduser -u 1000 -G appuser -s /bin/sh -D appuser

USER appuser

WORKDIR /app
