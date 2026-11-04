#!/bin/sh
# Prepares the app on every start: key, database, demo data. Safe to run repeatedly.
set -e
cd /app

# tr strips Windows line endings that would otherwise hide an empty APP_KEY
[ -f .env ] || tr -d '\r' < .env.example > .env
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

exec "$@"
