#!/bin/bash
set -e

APP_DIR="/app"

# --------------------------------------------------
# Initialize Laravel project if it does not exist.
# --------------------------------------------------
if [ ! -f "$APP_DIR/artisan" ]; then
    TEMP_DIR="$APP_DIR/laravel_temp"
    composer create-project laravel/laravel "$TEMP_DIR" --prefer-dist

    cp -r "$TEMP_DIR"/. "$APP_DIR"/
    rm -rf "$TEMP_DIR"

    if [ ! -f "$APP_DIR/.env" ]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        php "$APP_DIR/artisan" key:generate
    fi

    chmod -R 777 storage
    composer require ronasit/laravel-project-initializator --dev

    git config --global --add safe.directory "$APP_DIR"

    echo
    read -rp $'\033[32mSet project name:\033[0m ' PROJECT_NAME

    php "$APP_DIR/artisan" init "$PROJECT_NAME"
fi

# Remove this script after execution
rm -- "$(realpath "${BASH_SOURCE[0]}")"