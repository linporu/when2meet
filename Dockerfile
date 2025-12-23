# ============================================
# Multi-stage Dockerfile for Laravel When2Meet
# Optimized for production deployment
# ============================================

# ============================================
# Stage 1: Composer Dependencies
# ============================================
FROM composer:2.8 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
# For production: use --no-dev flag
# For development: install all dependencies including dev packages
ARG INSTALL_DEV_DEPS=false
RUN if [ "$INSTALL_DEV_DEPS" = "true" ]; then \
        composer install \
            --no-scripts \
            --no-autoloader \
            --prefer-dist; \
    else \
        composer install \
            --no-dev \
            --no-scripts \
            --no-autoloader \
            --prefer-dist \
            --optimize-autoloader; \
    fi

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# ============================================
# Stage 2: Production Runtime (PHP 8.4)
# ============================================
FROM php:8.4-fpm-alpine AS production

# Metadata
LABEL maintainer="your-email@example.com"
LABEL description="Laravel When2Meet - Event Scheduling Application"
LABEL version="1.0.0"

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    # System utilities
    bash \
    curl \
    git \
    unzip \
    # PostgreSQL client
    postgresql-client \
    libpq-dev \
    # Image processing
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # Compression
    libzip-dev \
    zip \
    # Intl extension dependencies
    icu-dev \
    # Nginx for serving static files
    nginx \
    supervisor

# Install PHP extensions required by Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension (for future caching optimization)
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del pcre-dev $PHPIZE_DEPS

# Set working directory
WORKDIR /var/www/when2meet

# Copy application code from composer-builder
COPY --from=composer-builder --chown=www-data:www-data /app /var/www/when2meet

# Copy pre-compiled frontend assets (already in repo)
# /public/build is already included from previous COPY

# Create required directories and set permissions
RUN mkdir -p \
    /var/www/when2meet/storage/logs \
    /var/www/when2meet/storage/framework/cache \
    /var/www/when2meet/storage/framework/sessions \
    /var/www/when2meet/storage/framework/views \
    /var/www/when2meet/bootstrap/cache \
    /var/log/supervisor \
    /run/php && \
    chown -R www-data:www-data \
    /var/www/when2meet/storage \
    /var/www/when2meet/bootstrap/cache && \
    chmod -R 775 \
    /var/www/when2meet/storage \
    /var/www/when2meet/bootstrap/cache

# Copy PHP-FPM configuration
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/laravel.ini

# Copy Nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose ports
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start Supervisor (manages Nginx + PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ============================================
# Stage 3: Development Runtime (with Xdebug)
# ============================================
FROM production AS development

# Install Xdebug for debugging
RUN apk add --no-cache $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del $PHPIZE_DEPS

# Copy development PHP configuration
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/xdebug.ini

# Install Node.js and PNPM for frontend development
RUN apk add --no-cache nodejs npm && \
    npm install -g pnpm

# Switch to www-data user for development
USER www-data

CMD ["php-fpm"]
