FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    libicu-dev \
    zip \
    && docker-php-ext-install pdo pdo_pgsql intl

COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

CMD ["php-fpm"]
