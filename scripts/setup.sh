#!/bin/sh

# Create apps directory if it doesn't exist
mkdir -p apps

# Move into apps directory
cd apps

curl -s "https://laravel.build/api?with=mysql" | bash

# move to api directory
cd api

# Install Breeze API (tokens via Sanctum)
./vendor/bin/sail up -d
./vendor/bin/sail composer require laravel/breeze --dev
./vendor/bin/sail artisan breeze:install api
./vendor/bin/sail composer require laravel/sanctum
./vendor/bin/sail artisan migrate

# Swagger/OpenAPI for Laravel
./vendor/bin/sail composer require darkaonline/l5-swagger
./vendor/bin/sail artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"

# Stop the Sail containers
./vendor/bin/sail down

# Move back to the apps directory
cd ..

# Install Next.js (latest)
npx create-next-app@latest web --ts --eslint --app --use-npm

# Move back to the root directory
cd ..
