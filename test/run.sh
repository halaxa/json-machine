#!/usr/bin/env sh
printf "$(php -v | head -n 1)\n"
composer install && vendor/bin/phpunit --colors
