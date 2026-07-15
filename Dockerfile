# --- Laravel on Render (free tier) ---
# Real-time broadcasts fire in-request (ShouldBroadcastNow). The PREVIOUS setup
# used `php artisan serve`, which is a SINGLE-THREADED dev server: it handles one
# request at a time. During a call the browser fires a burst of signaling POSTs
# (offer/answer/ICE) at /chat/call-signal, and each one BLOCKS on its outbound
# Pusher broadcast. Serialized behind one thread, those pile up into a 30-40s
# delay before the caller receives the "answer" — the exact symptom we saw.
#
# FrankenPHP is a production PHP server with a real worker pool, so those signal
# requests are handled CONCURRENTLY instead of single-file. It also serves the
# static public/app.html directly.

# FrankenPHP bundles PHP 8.4 + a Caddy-based server in one binary.
FROM dunglas/frankenphp:1-php8.4

# git/unzip for composer; the FrankenPHP image bundles `install-php-extensions`,
# which pulls the right system libs AND is idempotent (skips already-present
# extensions instead of erroring like raw docker-php-ext-install would).
RUN apt-get update && apt-get install -y git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        pdo_pgsql pdo_mysql \
        mbstring bcmath zip gd intl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP deps first (better build caching).
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# App code.
# --no-scripts is REQUIRED: without it, dump-autoload runs `artisan package:discover`,
# which BOOTS the app at build time — but there's no .env/APP_KEY/DB during the build,
# so it crashes. We defer all artisan work to container start (entrypoint below).
COPY . .
RUN composer dump-autoload --optimize --no-scripts

# Render's container sandbox refuses the CAP_NET_BIND_SERVICE capability the
# frankenphp binary ships with, which makes it fail to exec with
# "Operation not permitted" (status 126). We never bind a privileged port —
# Render always hands us a high $PORT — so strip the capability entirely.
# This is FrankenPHP's documented fix for unprivileged/sandboxed environments.
RUN setcap -r /usr/local/bin/frankenphp

# Explicit Caddyfile controls how FrankenPHP serves the app (see the file).
COPY Caddyfile /etc/frankenphp/Caddyfile

# Start script: Render provides $PORT + env vars at runtime, so NOW we can boot the
# app — run package discovery, migrate, cache config/routes, then hand off to
# FrankenPHP. `exec` replaces the shell so FrankenPHP receives signals correctly.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["/usr/local/bin/docker-entrypoint.sh"]
