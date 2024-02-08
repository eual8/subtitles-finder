#!/usr/bin/env bash

## More info in Heroku doc page https://devcenter.heroku.com/articles/release-phase#design-considerations

## Steps to execute
php artisan migrate --force
php artisan storage:link
php artisan cache:clear
php artisan config:clear
