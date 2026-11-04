# ---- build the front-end assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY app ./app
RUN npm run build

# ---- PHP application served by FrankenPHP (Caddy + PHP in one binary)
FROM dunglas/frankenphp:1-php8.4
RUN install-php-extensions pdo_sqlite intl gd zip opcache
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chmod -R a+rwX storage bootstrap/cache database

ENV APP_ENV=production \
    APP_DEBUG=false \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=array \
    LOG_CHANNEL=stderr \
    PORT=8080 \
    SERVER_NAME=:8080

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["sh", "-c", "exec frankenphp php-server --root /app/public --listen :${PORT}"]
