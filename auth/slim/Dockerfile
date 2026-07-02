FROM composer:2 AS composer_stage

FROM php:8.3-cli-alpine
RUN apk add --no-cache git unzip
COPY --from=composer_stage /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json .
RUN composer install --no-dev --no-interaction --prefer-dist

COPY public ./public
COPY aws ./aws
COPY azure ./azure
COPY gcp ./gcp
COPY shared ./shared

EXPOSE 8007
CMD ["php", "-S", "0.0.0.0:8007", "-t", "public", "public/index.php"]
