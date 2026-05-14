FROM php:8.2-fpm

RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY ./www /var/www/html

RUN composer install

CMD ["php-fpm"]