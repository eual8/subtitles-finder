#!/usr/bin/env bash

## More info in Heroku doc page https://devcenter.heroku.com/articles/release-phase#design-considerations

## Steps to execute
php artisan migrate --force
php artisan storage:link
php artisan cache:clear
php artisan config:clear

# check for a good exit
if [ $? -ne 0 ]
then
  # something went wrong; convey that and exit
  exit 1
fi
