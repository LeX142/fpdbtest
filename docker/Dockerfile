FROM php:8.3-fpm-alpine

ENV TZ=US/Eastern
ENV DEBIAN_FRONTEND="noninteractive"

# Install dependencies
RUN set -eux \
    && apk update \
    && apk add --no-cache \
        c-client \
        ca-certificates \
        make \
        tzdata \
        bash
\
#############################################
### Install and enable PHP extensions
#############################################
\
# Development dependencies
RUN set -eux \
    && apk add --no-cache --virtual .build-deps \
        linux-headers \
        autoconf \
        automake \
        build-base \
        cmake \
        curl-dev \
        gawk \
        gcc \
        pkgconf \
        php83-dev \
\
################################
# Install PHP extensions
################################
\
# Install mysqli
    && docker-php-ext-install -j$(nproc) mysqli \
    && true \
\
# Install pdo_mysql
    && docker-php-ext-configure pdo_mysql --with-zlib-dir=/usr \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && true \
\
# Install pcntl
#    && docker-php-ext-install -j$(nproc) pcntl \
#    && true \
#\
# Install xdebug
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && true \
\
# Clean up build packages
    && docker-php-source delete \
    && apk del .build-deps \
    && true

RUN set -eux \
# Fix php.ini settings for enabled extensions
    && chmod +x "$(php -r 'echo ini_get("extension_dir");')"/* \
# Shrink binaries
    && (find /usr/local/bin -type f -print0 | xargs -n1 -0 strip --strip-all -p 2>/dev/null || true) \
    && (find /usr/local/lib -type f -print0 | xargs -n1 -0 strip --strip-all -p 2>/dev/null || true) \
    && (find /usr/local/sbin -type f -print0 | xargs -n1 -0 strip --strip-all -p 2>/dev/null || true) \
    && ln -s /etc/ssl/cert.pem /etc/ssl/certs/cacert.pem \
    && true

COPY php.ini /usr/local/etc/php/php.ini
