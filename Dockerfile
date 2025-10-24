# Imagen con Nginx + PHP-FPM ya integrada (simple para Laravel)
FROM webdevops/php-nginx:8.2

# Document root
ENV WEB_DOCUMENT_ROOT=/app/public
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=120
ENV PHP_POST_MAX_SIZE=32M
ENV PHP_UPLOAD_MAX_FILESIZE=32M

WORKDIR /app

# Instala dependencias del sistema necesarias para Laravel (extensiones ya vienen en la imagen)
RUN apt-get update -y && apt-get install -y \
    git unzip libzip-dev libpng-dev && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia composer.* primero para cache de dependencias
COPY composer.json composer.lock* /app/

# Instala dependencias sin dev (ajústalo si necesitas dev)
RUN composer install --no-dev --no-interaction --no-ansi --no-progress --prefer-dist --optimize-autoloader

# Copia el resto del proyecto
COPY . /app

# Permisos para storage y cache
RUN chown -R application:application /app && \
    chmod -R ug+rwX storage bootstrap/cache

# Genera la clave de la app si no existe (Render puede inyectar APP_KEY; si no, la generamos)
# Importante: --force para sobreescribir si el archivo ya existe sin clave
RUN php artisan key:generate --force || true

# Optimiza
RUN php artisan route:cache || true && \
    php artisan config:cache || true && \
    php artisan view:cache || true
