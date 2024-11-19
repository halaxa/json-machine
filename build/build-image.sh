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

printf "
    FROM $FROM_IMAGE
    RUN apk update && apk upgrade
    # https://stackoverflow.com/questions/76507083/pecl-install-no-releases-available#comment136513209_76651916
    RUN rm /etc/ssl/certs/ca-cert-DST_Root_CA_X3.pem || true \
      && cat /etc/ssl/certs/*.pem > /etc/ssl/certs/ca-certificates.crt \
      && cat /etc/ssl/certs/*.pem > /etc/ssl/cert.pem
    RUN apk add \
        autoconf \
        g++ \
        libtool \
        make \
        bash \
        linux-headers \
    && wget http://pear.php.net/go-pear.phar && php go-pear.phar \
    && pecl install xdebug-$XDEBUG_VERSION \
        && docker-php-ext-enable xdebug \
        && docker-php-ext-enable opcache \
    && wget https://getcomposer.org/download/2.8.1/composer.phar -O /usr/local/bin/composer \
        && chmod +x /usr/local/bin/composer
" | docker build --quiet --tag "$CONTAINER_NAME" - > /dev/null

echo "$CONTAINER_NAME"
