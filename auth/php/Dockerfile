FROM php:8.3-cli-alpine

WORKDIR /app
COPY router.php .

EXPOSE 8006
CMD ["php", "-S", "0.0.0.0:8006", "router.php"]
