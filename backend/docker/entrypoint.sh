#!/usr/bin/env sh
set -e

# Discover packages (safe to run repeatedly)
php artisan package:discover --ansi >/dev/null 2>&1 || true

# Ensure the public storage symlink exists
if [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

# Ensure the updates directory (bind-mounted from the host) exists and is
# writable by PHP-FPM, otherwise admin release uploads fail with
# "Unable to write in the ... directory".
if [ -d /var/www/updates ] || mkdir -p /var/www/updates 2>/dev/null; then
    mkdir -p /var/www/updates/android /var/www/updates/windows 2>/dev/null || true
    chown -R www-data:www-data /var/www/updates 2>/dev/null || true
    chmod -R u+rwX,g+rwX /var/www/updates 2>/dev/null || true
fi

exec "$@"
