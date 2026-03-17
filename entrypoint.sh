#!/bin/bash
composer install

if [[ -f .env ]]; then
  echo ".env already exists"
else
  cp .env.example .env
  php artisan key:generate
  php artisan jwt:secret
fi

php artisan migrate --force
chmod -R 777 storage
