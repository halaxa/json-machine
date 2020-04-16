#!/usr/bin/env sh
printf "$(php -v | head -n 1)\n"
composer update && vendor/bin/phpunit --colors
