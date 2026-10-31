
# Stage 1: Build frontend assets
FROM node:24 AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# Include Vite, Tailwind, PostCSS, and other project configuration
COPY . .
# Tailwind is loaded via CDN in Blade; Vite build is optional for this app.
RUN npm run build || mkdir -p public/build && echo "{}" > public/build/manifest.json


# Stage 2: Run Laravel with PHP and Apache
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache URL rewriting
RUN a2enmod rewrite

# Configure Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Configure Apache for Render's port
RUN sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
    /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Fail the build if site logos are missing (avoids silent 404s on Render)
RUN ls -la public && ls -la public/icons || true \
    && test -f public/icons/logo.png \
    && test -f public/logo.png \
    && chmod 644 public/icons/logo.png public/logo.png \
    || (echo "ERROR: logo PNG missing in image — check Git includes public/icons/logo.png" && exit 1)

# Copy compiled Vite assets
COPY --from=frontend /app/public/build ./public/build

# Install production dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Set Laravel writable directory permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Render: ensure APP_KEY exists (Laravel 500s without it)
CMD ["sh", "-c", "if [ -z \"$APP_KEY\" ]; then export APP_KEY=base64:$(openssl rand -base64 32); echo \"Generated ephemeral APP_KEY — set APP_KEY in Render env for stable sessions\"; fi; php artisan config:clear; php artisan route:clear; php artisan view:clear; apache2-foreground"]
