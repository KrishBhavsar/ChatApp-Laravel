#!/bin/sh
set -e

# Runtime boot (env vars + DB are only available now, not at build time).
php artisan package:discover --ansi
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# FrankenPHP reads the listen address from SERVER_NAME. Render provides $PORT;
# ":<port>" binds all interfaces on that port (FrankenPHP's documented syntax).
# We build a Caddyfile so behaviour is explicit and version-independent rather
# than relying on undocumented CLI flags.
export SERVER_NAME=":${PORT:-8000}"

# Serve with FrankenPHP (concurrent worker pool) instead of the single-threaded
# `php artisan serve`. This is what lets the burst of call-signaling POSTs be
# handled in parallel instead of queuing single-file behind each other's
# blocking Pusher broadcast — the cause of the 30-40s answer delay.
# exec so FrankenPHP is PID 1 and receives container signals cleanly.
exec frankenphp run --config /etc/frankenphp/Caddyfile
