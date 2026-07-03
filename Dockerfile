# --- Laravel on Render (free tier) ---
# Real-time broadcasts fire in-request (ShouldBroadcastNow), so NO queue worker
# is needed — a single web process is enough.

# PHP 8.4 — your composer.lock pulled Symfony 8 packages that require php >= 8.4.
FROM php:8.4-cli

# System libraries needed to COMPILE the PHP extensions below.
# NOTE: curl, dom, xml, fileinfo, pdo are already bundled in the base image — do NOT
# reinstall them (that errors). Only install the ones actually missing.
RUN apt-get update && apt-get install -y \
    git unzip \
    libzip-dev libpq-dev libonig-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql pdo_mysql \
        mbstring bcmath zip gd intl \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP deps first (better build caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# App code.
# --no-scripts is REQUIRED: without it, dump-autoload runs `artisan package:discover`,
# which BOOTS the app at build time — but there's no .env/APP_KEY/DB during the build,
# so it crashes. We defer all artisan work to container start (CMD below).
COPY . .
RUN composer dump-autoload --optimize --no-scripts

# Render provides $PORT + all env vars at runtime. NOW the app can boot, so we run
# package discovery, migrate, cache, and serve — all at container start.
CMD php artisan package:discover --ansi \
    && php artisan migrate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
