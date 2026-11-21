#!/usr/bin/env sh

set -e

PHP_VERSION=$1
XDEBUG_VERSION=$2
XDEBUG=$3

FROM_IMAGE="php:$PHP_VERSION-cli-alpine"
CONTAINER_NAME="json-machine-php-$PHP_VERSION"

INSTALL_XDEBUG=
if [ "$XDEBUG" = "1" ] && [ -n "$XDEBUG_VERSION" ]; then
    INSTALL_XDEBUG=1
    CONTAINER_NAME="$CONTAINER_NAME-xdebug"
fi

docker ps --all --format "{{.Names}}" | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"

>&2 echo "Building $CONTAINER_NAME from $FROM_IMAGE"

echo "
    FROM $FROM_IMAGE

    RUN apk update && apk upgrade

    RUN apk add --no-cache bash

    RUN php -i | grep -q opcache || docker-php-ext-enable opcache

    RUN php -m | grep -qi '^mbstring$' || ( \
        apk add --no-cache oniguruma \
        && apk add --no-cache --virtual .mbstring-build-deps oniguruma-dev \
        && docker-php-ext-install mbstring \
        && apk del .mbstring-build-deps \
    )

    RUN wget https://getcomposer.org/download/2.9.2/composer.phar -O /usr/local/bin/composer \
        && chmod +x /usr/local/bin/composer

    RUN if [ \"$INSTALL_XDEBUG\" = \"1\" ]; then \
        apk add --no-cache --virtual .xdebug-build-deps autoconf g++ make linux-headers \
        && pecl install xdebug-$XDEBUG_VERSION \
        && docker-php-ext-enable xdebug \
        && apk del .xdebug-build-deps; \
      fi
" | docker build --quiet --tag "$CONTAINER_NAME" - > /dev/null

echo "$CONTAINER_NAME"
