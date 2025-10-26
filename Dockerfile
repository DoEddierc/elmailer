FROM webdevops/php-nginx:8.2

ENV WEB_DOCUMENT_ROOT=/app/public
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=120
ENV PHP_POST_MAX_SIZE=32M
ENV PHP_UPLOAD_MAX_FILESIZE=32M

WORKDIR /app

RUN apt-get update -y && apt-get install -y \
    git unzip libzip-dev libpng-dev && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cache de dependencias
COPY composer.json composer.lock* /app/
RUN composer install --no-dev --no-interaction --no-ansi --no-progress --prefer-dist --optimize-autoloader --no-scripts

# Copia código
COPY . /app

# Permisos
RUN chown -R application:application /app && \
    chmod -R ug+rwX storage bootstrap/cache

# Descubrir paquetes y LIMPIAR caches (no generar)
RUN php artisan package:discover --ansi || true && \
    php artisan config:clear || true && \
    php artisan route:clear  || true && \
    php artisan view:clear   || true && \
    php artisan cache:clear  || true

# La imagen base ya inicia nginx + php-fpm (puerto 80)
