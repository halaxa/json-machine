#!/usr/bin/env sh
set -e
IFS=:
for VERSION in \
  5.6:5.6:2.5.5 \
  7.0:7.0:2.6.1 \
  7.1:7.1:2.9.0 \
  7.2:7.2:2.9.0 \
  7.3:7.3:2.9.0 \
  7.4:7.4:2.9.0 \
  8.0:8.0.0RC3:3.0.0beta1
do
    set -- $VERSION
    PHP_VERSION=$1
    DOCKER_IMAGE_TAG=$2
    XDEBUG_VERSION=$3

    printf "\n\n"

    printf "PHP $PHP_VERSION\n"
    printf "===============================\n"

    PHP_IMAGE="php:$DOCKER_IMAGE_TAG-cli-alpine"
    CONTAINER_NAME="json-machine-php-$PHP_VERSION"

    docker ps --all --format "{{.Names}}" | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"

    IS_RECENT_IMAGE="$([ ! -z "$TMPDIR"] && printf "$TMPDIR" || printf "/tmp")/json-machine-php-$PHP_VERSION-$(date +"%Y-%m-%d")"
    if [ ! -f "$IS_RECENT_IMAGE" ]; then
        printf "Checking for new version of PHP $PHP_VERSION docker image...\n"
        docker pull "$PHP_IMAGE" > /dev/null
        touch $IS_RECENT_IMAGE
    fi

    printf "Building a dev docker image ...\n"
    printf "
        FROM $PHP_IMAGE
        RUN apk add --update \
            autoconf \
            g++ \
            libtool \
            make
        RUN wget http://pear.php.net/go-pear.phar && php go-pear.phar
        RUN pecl install xdebug-$XDEBUG_VERSION
        RUN wget https://getcomposer.org/composer.phar -O /usr/local/bin/composer \
              && chmod +x /usr/local/bin/composer
    " | docker build --tag "$CONTAINER_NAME" - > /dev/null
    printf "Running tests...\n"
    docker run -it --rm \
        --name "$CONTAINER_NAME" \
        --volume "$PWD:/usr/src/json-machine" \
        --workdir "/usr/src/json-machine" \
        --user "$(id -u):$(id -g)" \
        --env COMPOSER_CACHE_DIR=/dev/null \
        "$CONTAINER_NAME" \
        test/run.sh "$@" || true
done
