#!/bin/sh
# Prepares the app on every start: key, database, demo data. Safe to run repeatedly.
set -e
cd /app

# tr strips Windows line endings that would otherwise hide an empty APP_KEY
[ -f .env ] || tr -d '\r' < .env.example > .env
if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Optional Telegram bot: register the webhook and run the scheduler (appointment reminders)
if [ -n "$TELEGRAM_TOKEN" ]; then
    case "$APP_URL" in
        https://*) php artisan salon:bot-setup --url="$APP_URL/telegram/webhook" --no-interaction || echo "Telegram setup failed, continuing" ;;
    esac
    ( while true; do php artisan schedule:run --no-interaction >/dev/null 2>&1; sleep 60; done ) &
fi

exec "$@"
