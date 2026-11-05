#!/usr/bin/env sh
set -e

# Discover packages (safe to run repeatedly)
php artisan package:discover --ansi >/dev/null 2>&1 || true

# Ensure the public storage symlink exists
if [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"
