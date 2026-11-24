#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

node /var/www/html/bin/octane-poll-reload.cjs &
WATCHER_PID=$!

cleanup() {
    kill "${WATCHER_PID}" 2>/dev/null || true
}

trap cleanup EXIT INT TERM

exec /usr/bin/php -d variables_order=EGPCS /var/www/html/artisan octane:start \
    --server=frankenphp \
    --host=0.0.0.0 \
    --admin-port=2019 \
    --port="${APP_PORT:-80}"
