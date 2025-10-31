ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli

# Set shell options for better error handling
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# System dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip zip \
        $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# PHP Extensions (minimal set for testing)
RUN docker-php-ext-install -j"$(nproc)" \
    curl \
    mbstring \
    intl \
    zip

# Install Xdebug (will be enabled/disabled via environment variable)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug || true

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

# PHP Configuration
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini \
    && echo "date.timezone = UTC" >> /usr/local/etc/php/conf.d/docker-php-timezone.ini

# Entrypoint script for Xdebug control
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /app

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php"]

