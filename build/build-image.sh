#!/usr/bin/env sh

set -e

PHP_VERSION=$1
XDEBUG_VERSION=$2

FROM_IMAGE="php:$PHP_VERSION-cli-alpine"
CONTAINER_NAME="json-machine-php-$PHP_VERSION"

docker ps --all --format "{{.Names}}" | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"

>&2 echo "Building $CONTAINER_NAME from $FROM_IMAGE"

echo "
    FROM $FROM_IMAGE

    RUN apk update && apk upgrade

    RUN apk add --no-cache bash

    RUN php -i | grep -q opcache || docker-php-ext-enable opcache

    RUN php -m | grep -qi '^mbstring$' || ( \
        apk add --no-cache --virtual .mbstring-build-deps oniguruma-dev \
        && docker-php-ext-install mbstring \
        && apk del .mbstring-build-deps \
    )

    RUN wget https://getcomposer.org/download/2.9.2/composer.phar -O /usr/local/bin/composer \
        && chmod +x /usr/local/bin/composer

    RUN if [ -n \"$XDEBUG_VERSION\" ]; then \
        apk add --no-cache --virtual .xdebug-build-deps autoconf g++ make linux-headers \
        && pecl install xdebug-$XDEBUG_VERSION \
        && docker-php-ext-enable xdebug \
        && apk del .xdebug-build-deps; \
      fi
" | docker build --quiet --tag "$CONTAINER_NAME" - > /dev/null

echo "$CONTAINER_NAME"
