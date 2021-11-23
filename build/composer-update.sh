#!/usr/bin/env sh

if [ $(php -r "echo PHP_VERSION_ID;") -lt 70200 ]
then
  set -x
  COMPOSER=build/composer-lt-7.2.json composer --quiet update
else
  set -x
  composer --quiet update
fi
