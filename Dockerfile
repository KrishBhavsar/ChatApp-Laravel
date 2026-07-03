# --- Laravel on Render (free tier) ---
# Real-time broadcasts fire in-request (ShouldBroadcastNow), so NO queue worker
# is needed — a single web process is enough.

FROM php:8.2-cli

# System deps + PHP extensions Laravel needs (incl. pdo_pgsql for Render Postgres).
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql mbstring bcmath zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP deps first (better build caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# App code
COPY . .
RUN composer dump-autoload --optimize

# Render provides $PORT at runtime. Migrate + cache, then serve.
# (artisan serve is acceptable for a small app; upgrade to Octane/FrankenPHP later.)
CMD php artisan migrate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
