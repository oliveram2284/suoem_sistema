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
RUN docker-php-ext-install gd  # Does GD install work now?
RUN docker-php-ext-install pdo # Does PDO install work? (usually built-in)
RUN docker-php-ext-install pdo_pgsql # Does PGSQL install work? This is critical.
RUN docker-php-ext-install zip # Does ZIP install work?

RUN docker-php-ext-enable opcache # Opcache sometimes needs explicit enable depending on base image
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/zz-opcache-custom.ini

# Configuraciones adicionales de PHP para reducir warnings de deprecación
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/zz-php-custom.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/zz-php-custom.ini
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/zz-php-custom.ini

# For intl extension, you usually need to install libicu-dev first
RUN apt-get update && apt-get install -y libicu-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) intl

# Instala Composer (versión más reciente compatible con PHP 8.4)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configura el directorio de trabajo
WORKDIR /var/www

# Copia archivos del proyecto
COPY . .

# Asigna permisos
RUN chown -R www-data:www-data /var/www

# Exponemos el puerto
EXPOSE 9000

CMD ["php-fpm"]
