#!/usr/bin/env sh
IFS=:
for VERSION in \
  5.6:2.5.5 \
  7.0:2.6.1 \
  7.1:2.6.1 \
  7.2:2.6.1 \
  7.3:beta
do
    set -e
    set -- $VERSION
    PHP_VERSION=$1
    XDEBUG_VERSION=$2
    PHP_IMAGE="php:$PHP_VERSION-cli-alpine"
    CONTAINER_NAME="json-machine-php-$PHP_VERSION"

    docker ps -a | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"
    docker pull "$PHP_IMAGE"
    printf  "
        FROM $PHP_IMAGE
        RUN apk add --update \
            autoconf \
            g++ \
            libtool \
            make \
            && pecl install xdebug-$XDEBUG_VERSION \
            && docker-php-ext-enable xdebug
    " | docker build --tag "$CONTAINER_NAME" -
    docker run -it --rm \
        --name "$CONTAINER_NAME" \
        --volume "$PWD:/usr/src/json-machine" \
        --workdir "/usr/src/json-machine" \
        --user "$(id -u):$(id -g)" \
        --env COMPOSER_CACHE_DIR=/dev/null \
        "$CONTAINER_NAME" \
        test/run.sh "$@"
done;
