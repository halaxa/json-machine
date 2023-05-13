#!/usr/bin/env sh

set -e

CONTAINER_NAME=$1
shift
PROJECT_DIR=$1
shift

docker run $DOCKER_RUN_OPTS --rm \
  --name "$CONTAINER_NAME" \
  --volume "$PROJECT_DIR:/project" \
  --volume "/tmp:/tmp" \
  --workdir "/project" \
  --user "$(id -u):$(id -g)" \
  --env COMPOSER_CACHE_DIR=/tmp \
  "$CONTAINER_NAME" \
  /bin/bash -c "$@"
