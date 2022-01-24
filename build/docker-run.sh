#!/usr/bin/env sh

set -e

CONTAINER_NAME=$1
shift
PROJECT_DIR=$1
shift

docker run --rm \
  --name "$CONTAINER_NAME" \
  --volume "$PROJECT_DIR:/usr/src/json-machine" \
  --volume "/tmp:/tmp" \
  --workdir "/usr/src/json-machine" \
  --user "$(id -u):$(id -g)" \
  --env COMPOSER_CACHE_DIR=/tmp \
  "$CONTAINER_NAME" \
  /bin/bash -c "$@"
