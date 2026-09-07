#!/bin/sh
set -e

# Bind-mounted volumes arrive empty, so the framework's scratch directories have
# to be recreated on every boot rather than relying on what the image shipped.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/app/public \
    storage/logs \
    bootstrap/cache

DB_PATH="${DB_DATABASE:-/var/lib/lettuce-eat/database.sqlite}"
mkdir -p "$(dirname "$DB_PATH")"
[ -f "$DB_PATH" ] || touch "$DB_PATH"

php artisan migrate --force

# Caches are built at boot, not at image build time, because the environment
# only exists once the container starts.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
