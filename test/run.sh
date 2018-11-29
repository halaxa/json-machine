#!/usr/bin/env sh
printf "\n\n$(php -v | head -n 1)\n"
[ ! -f vendor/autoload.php ] && php ./composer install
php vendor/bin/phpunit --colors
