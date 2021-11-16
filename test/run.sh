#!/usr/bin/env sh
set -e
printf "$(php -v | head -n 1)\n"

rm -f composer.lock
composer remove --dev phpunit/phpunit

if [ $(php -r "echo PHP_VERSION_ID;") -lt 80000 ]
then
  composer require --dev phpunit/phpunit ^5 --ignore-platform-req=php
else
  composer require --dev phpunit/phpunit ^8 --ignore-platform-req=php
fi

composer update --ignore-platform-req=php
set -x

vendor/bin/phpunit $@
