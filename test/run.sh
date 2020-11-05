#!/usr/bin/env sh
printf "$(php -v | head -n 1)\n"
composer update --ignore-platform-req=php && vendor/bin/phpunit --colors
