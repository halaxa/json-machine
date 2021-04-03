#!/usr/bin/env sh
set -e
printf "$(php -v | head -n 1)\n"
composer update --ignore-platform-req=php
set -x
vendor/bin/phpunit $@
