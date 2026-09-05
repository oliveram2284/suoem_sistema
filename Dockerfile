FROM php:8.4-fpm

# Instala dependencias necesarias
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    libpq-dev \
    libwebp-dev \
    libxpm-dev \
    libgif-dev \
    vim

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install zip

RUN docker-php-ext-enable opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini

RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/zz-php-custom.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/zz-php-custom.ini
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/zz-php-custom.ini

RUN apt-get update && apt-get install -y libicu-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache/data \
             storage/logs \
             bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && composer dump-autoload --optimize --no-dev --no-scripts

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]