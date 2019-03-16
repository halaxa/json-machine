#!/usr/bin/env sh
set -e
IFS=:
for VERSION in \
  5.6:2.5.5 \
  7.0:2.6.1 \
  7.1:2.6.1 \
  7.2:2.6.1 \
  7.3:beta
do
    set -- $VERSION
    PHP_VERSION=$1
    XDEBUG_VERSION=$2

    printf "$SEPARATOR"
    SEPARATOR="\n\n"

    PHP_IMAGE="php:$PHP_VERSION-cli-alpine"
    CONTAINER_NAME="json-machine-php-$PHP_VERSION"

    docker ps --all --format "{{.Names}}" | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"
    echo "Checking for new version of PHP $PHP_VERSION docker image..."
    docker pull "$PHP_IMAGE" > /dev/null
    echo "Building a dev image on top of it..."
    printf  "
        FROM $PHP_IMAGE
        RUN apk add --update \
            autoconf \
            g++ \
            libtool \
            make \
            && pecl install xdebug-$XDEBUG_VERSION \
              && docker-php-ext-enable xdebug \
            && wget https://getcomposer.org/composer.phar -O /usr/local/bin/composer \
              && chmod +x /usr/local/bin/composer
    " | docker build --tag "$CONTAINER_NAME" - > /dev/null
    echo "Running tests..."
    docker run -it --rm \
        --name "$CONTAINER_NAME" \
        --volume "$PWD:/usr/src/json-machine" \
        --workdir "/usr/src/json-machine" \
        --user "$(id -u):$(id -g)" \
        --env COMPOSER_CACHE_DIR=/dev/null \
        "$CONTAINER_NAME" \
        test/run.sh "$@"
done
