#!/usr/bin/env sh

set -e

PHP_MINOR=$1
PHP_VERSION=$( (wget -qO- "https://hub.docker.com/v2/repositories/library/php/tags?page_size=100&name=$PHP_MINOR" \
  | grep -Po "[0-9]+\.[0-9]+\.[0-9]+(?=-)" \
    || echo "$PHP_MINOR") \
  | head -1 \
)
XDEBUG_VERSION=$2


FROM_IMAGE="php:$PHP_VERSION-cli-alpine"
CONTAINER_NAME="json-machine-php-$PHP_VERSION"


docker ps --all --format "{{.Names}}" | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"

>&2 echo "Building $CONTAINER_NAME from $FROM_IMAGE"

# Use pecl for PHP < 8.1, pie for PHP >= 8.1
if [ "$(printf '%s\n' "8.1" "$PHP_MINOR" | sort -V | head -n1)" = "8.1" ]; then
    PIE_COPY="COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie"
    XDEBUG_INSTALL="pie install xdebug/xdebug:$XDEBUG_VERSION"
else
    PIE_COPY=""
    XDEBUG_INSTALL="pecl install xdebug-$XDEBUG_VERSION && docker-php-ext-enable xdebug"
fi

printf "
    FROM $FROM_IMAGE

    $PIE_COPY

    RUN apk add \
        autoconf \
        g++ \
        libtool \
        make \
        bash \
        linux-headers \
    && $XDEBUG_INSTALL \
    && wget https://getcomposer.org/download/2.8.12/composer.phar -O /usr/local/bin/composer \
        && chmod +x /usr/local/bin/composer
" | docker build --quiet --tag "$CONTAINER_NAME" - > /dev/null

echo "$CONTAINER_NAME"