#!/usr/bin/env sh

set -e

PHP_VERSION=$1
XDEBUG_VERSION=$2


FROM_IMAGE="php:$PHP_VERSION-cli-alpine"
CONTAINER_NAME="json-machine-php-$PHP_VERSION"

if [ "$2" = "--pull" ]
then
  set -x
  docker pull "$FROM_IMAGE"
  exit
fi

docker ps --all --format "{{.Names}}" | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"

>&2 echo "Building $CONTAINER_NAME from $FROM_IMAGE"

printf "
    FROM $FROM_IMAGE
    RUN apk add --update \
        autoconf \
        g++ \
        libtool \
        make \
    && wget http://pear.php.net/go-pear.phar && php go-pear.phar \
    && pecl install xdebug-$XDEBUG_VERSION \
        && docker-php-ext-enable xdebug \
    && wget https://getcomposer.org/download/latest-stable/composer.phar -O /usr/local/bin/composer \
        && chmod +x /usr/local/bin/composer
" | docker build --quiet --tag "$CONTAINER_NAME" - > /dev/null

echo "$CONTAINER_NAME"
