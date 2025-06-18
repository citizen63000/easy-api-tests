#!/bin/bash

cat > Dockerfile << 'EOL'
FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    git unzip libzip-dev icu-dev \
    && docker-php-ext-install zip intl pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN git config --global --add safe.directory /app

RUN addgroup -g 1000 appuser && \
    adduser -u 1000 -G appuser -s /bin/sh -D appuser

RUN chown -R appuser:appuser /app

USER appuser

WORKDIR /app
EOL

cat > docker-compose.yml << 'EOL'
services:
  app:
    container_name: app
    build: .
    volumes:
      - .:/app
    working_dir: /app
    command: tail -f /dev/null
EOL

echo -e "Docker stack created.\nRun it with\n\ndocker compose up -d"
