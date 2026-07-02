FROM php:8.3-cli-alpine

WORKDIR /app
COPY router.php .
COPY aws ./aws
COPY azure ./azure
COPY gcp ./gcp
COPY shared ./shared

EXPOSE 8006
CMD ["php", "-S", "0.0.0.0:8006", "router.php"]
