#!/usr/bin/env bash
set -e

cd /var/www/html

# Install PHP dependencies if they're missing (e.g. fresh checkout / clean prod build).
if [ ! -d vendor ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate an app key if one isn't set.
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# The 'app' service runs first-boot DB setup; workers skip it.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] Running migrations (waiting for the database to accept connections)..."
    for i in $(seq 1 20); do
        if php artisan migrate --force --seed; then
            break
        fi
        echo "[entrypoint] DB not ready yet (attempt $i/20), retrying in 3s..."
        sleep 3
    done

    php artisan storage:link || true
fi

# Ensure runtime dirs are writable.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
