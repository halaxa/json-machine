#!/usr/bin/env sh
printf "\n\n$(php -v | head -n 1)\n"
composer install && vendor/bin/phpunit --colors
