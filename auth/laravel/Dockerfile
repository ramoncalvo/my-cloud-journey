FROM composer:2 AS composer_stage

FROM php:8.3-cli-alpine AS build
RUN apk add --no-cache git unzip
COPY --from=composer_stage /usr/bin/composer /usr/bin/composer

WORKDIR /app
# Scaffolding oficial de Laravel via composer create-project: evita tener
# que escribir a mano las decenas de archivos base del framework
# (bootstrap/, config/, artisan, .env con APP_KEY generada, etc).
RUN composer create-project laravel/laravel . --no-dev --no-interaction --prefer-dist --no-audit
RUN composer require league/oauth2-client:^2.7 --no-interaction

# Screaming architecture por encima del esqueleto: app/Aws, app/Azure,
# app/Gcp con su Client + Controller, y las rutas que los conectan.
COPY overlay/routes/web.php routes/web.php
COPY overlay/app/Aws app/Aws
COPY overlay/app/Azure app/Azure
COPY overlay/app/Gcp app/Gcp
COPY overlay/app/Shared app/Shared

FROM php:8.3-cli-alpine
WORKDIR /app
COPY --from=build /app /app

EXPOSE 8008
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8008"]
