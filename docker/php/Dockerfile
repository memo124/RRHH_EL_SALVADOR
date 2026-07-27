FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/memory_limit = .*/memory_limit = 512M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI_DIR/php.ini"

USER www-data
