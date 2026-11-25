#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

if [ "${APP_ENV:-local}" = "local" ] || [ "${APP_ENV:-local}" = "development" ]; then
    if [ -f /var/www/html/bin/octane-poll-reload.cjs ] && [ -d /var/www/html/node_modules/chokidar ]; then
        node /var/www/html/bin/octane-poll-reload.cjs &
        WATCHER_PID=$!

        cleanup() {
            kill "${WATCHER_PID}" 2>/dev/null || true
        }

        trap cleanup EXIT INT TERM
    fi
fi

exec /usr/bin/php -d variables_order=EGPCS /var/www/html/artisan octane:start \
    --server=frankenphp \
    --host=0.0.0.0 \
    --admin-port=2019 \
    --port="${APP_PORT:-80}"
