#!/usr/bin/env sh
for VERSION in \
  5.6 \
  7.0 \
  7.1 \
  7.2 \
  7.3
do
    set -e
    CONTAINER_NAME="json-machine-php-$VERSION";

    docker ps -a | grep "$CONTAINER_NAME" && docker rm -f "$CONTAINER_NAME"
    docker run -it --rm \
        --name "$CONTAINER_NAME" \
        --volume "$PWD:/usr/src/json-machine" \
        --workdir "/usr/src/json-machine" \
        --user "$(id -u):$(id -g)" \
        --env COMPOSER_CACHE_DIR=/dev/null \
        "php:$VERSION-cli-alpine" \
        test/run.sh
done;
