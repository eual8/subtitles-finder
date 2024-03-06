web: vendor/bin/heroku-php-nginx -F fpm_custom.conf -C nginx_app.conf public/
release: ./release-tasks.sh
worker: php artisan queue:restart && php artisan queue:work --tries=3
