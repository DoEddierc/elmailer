FROM webdevops/php-nginx:8.2

ENV WEB_DOCUMENT_ROOT=/app/public
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=120
ENV PHP_POST_MAX_SIZE=32M
ENV PHP_UPLOAD_MAX_FILESIZE=32M

WORKDIR /app

RUN apt-get update -y && apt-get install -y git unzip libzip-dev libpng-dev && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia composer.* y instala sin scripts (artisan aún no existe)
COPY composer.json composer.lock* /app/
RUN composer install --no-dev --no-interaction --no-ansi --no-progress --prefer-dist --optimize-autoloader --no-scripts

# Ahora sí copia el resto del código
COPY . /app

# Permisos y optimizaciones
RUN chown -R application:application /app && \
    chmod -R ug+rwX storage bootstrap/cache

# Ejecuta scripts artisan ahora que el archivo existe
RUN php artisan package:discover --ansi || true
RUN php artisan key:generate --force || true
RUN php artisan route:cache || true && \
    php artisan config:cache || true && \
    php artisan view:cache || true
