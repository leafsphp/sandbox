#!/usr/bin/env bash

# syntax = docker/dockerfile:experimental

# Default to PHP 8.2, but we attempt to match
# the PHP version from the user (wherever `flyctl launch` is run)
# Valid version values are PHP 7.4+
ARG PHP_VERSION=8.2
ARG NODE_VERSION=18
FROM --platform=linux/amd64 mychidarko/leaf-fly-fpm:${PHP_VERSION} as base

# PHP_VERSION needs to be repeated here
# See https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION

LABEL fly_launch_runtime="leaf"

# copy application code, skipping files based on .dockerignore
COPY . /var/www/html

RUN composer install --optimize-autoloader --no-dev \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data /var/www/html \
    # && sed -i 's/protected \$proxies/protected \$proxies = "*"/g' app/Http/Middleware/TrustProxies.php \
    # && echo "MAILTO=\"\"\n* * * * * www-data /usr/bin/php /var/www/html/artisan schedule:run" > /etc/cron.d/laravel \
    && cp .fly/entrypoint.sh /entrypoint \
    && chmod +x /entrypoint

# Multi-stage build: Build static assets
# This allows us to not include Node within the final container
FROM node:${NODE_VERSION} as node_modules_go_brrr

RUN mkdir /app

RUN mkdir -p  /app
WORKDIR /app
COPY . .
COPY --from=base /var/www/html/vendor /app/vendor

# Note: We run "production" for Mix and "build" for Vite
RUN if [ -f "vite.config.js" ]; then \
    ASSET_CMD="build"; \
    fi;

# Use yarn or npm depending on what type of
# lock file we might find. Defaults to
# NPM if no lock file is found.
RUN if [ -f "yarn.lock" ]; then \
    yarn install --frozen-lockfile; \
    yarn $ASSET_CMD; \
    elif [ -f "pnpm-lock.yaml" ]; then \
    corepack enable && corepack prepare pnpm@latest-7 --activate; \
    pnpm install --frozen-lockfile; \
    pnpm run $ASSET_CMD; \
    elif [ -f "package-lock.json" ]; then \
    npm ci --no-audit; \
    npm run $ASSET_CMD; \
    else \
    # if no lock file is found, we check if we have a package.json
    # and if so, we run npm install
    if [ -f "package.json" ]; then \
    npm install; \
    npm run $ASSET_CMD; \
    fi; \
    fi;

# From our base container created above, we
# create our final image, adding in static
# assets that we generated above
FROM base

# Packages like Laravel Nova may have added assets to the public directory
# or maybe some custom assets were added manually! Either way, we merge
# in the assets we generated above rather than overwrite them
COPY --from=node_modules_go_brrr /app/ /var/www/html/public-npm/
RUN rsync -ar /var/www/html/public-npm/ /var/www/html/ \
    && rm -rf /var/www/html/public-npm \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8080

ENTRYPOINT ["/entrypoint"]
