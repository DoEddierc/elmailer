# Imagen base con Nginx y PHP-FPM
FROM webdevops/php-nginx:8.2

# Ajustes de PHP
ENV WEB_DOCUMENT_ROOT=/app/public
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=120
ENV PHP_POST_MAX_SIZE=32M
ENV PHP_UPLOAD_MAX_FILESIZE=32M

WORKDIR /app

# Dependencias del sistema
RUN apt-get update -y && apt-get install -y \
    git unzip libzip-dev libpng-dev && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

# Copiamos Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiamos composer.* primero para cachear dependencias
COPY composer.json composer.lock* /app/

# Instalamos dependencias sin scripts (artisan aún no existe)
RUN composer install --no-dev --no-interaction --no-ansi --no-progress --prefer-dist --optimize-autoloader --no-scripts

# Copiamos todo el código del proyecto
COPY . /app

# Permisos requeridos por Laravel
RUN chown -R application:application /app && \
    chmod -R ug+rwX storage bootstrap/cache

# Descubrimos paquetes y generamos caches (ahora que artisan existe)
RUN php artisan package:discover --ansi || true
RUN php artisan route:cache   || true && \
    php artisan config:cache  || true && \
    php artisan view:cache    || true

# La imagen base ya expone Nginx y PHP-FPM (puerto 80)
