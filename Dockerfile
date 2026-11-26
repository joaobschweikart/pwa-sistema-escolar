# syntax=docker/dockerfile:1.7
# =============================================================================
# Imagem «dem-app» — Diário Escolar Municipal (DEM)
# Build multi-stage: base PHP -> dependências Composer -> build Vite -> produção
#   docker build --platform linux/amd64 -t dem-app .            (produção)
#   docker build --target dev -t dem-app:dev .                  (desenvolvimento)
# =============================================================================

ARG PHP_VERSION=8.5
ARG NODE_VERSION=24

# -----------------------------------------------------------------------------
# base: PHP-FPM com as extensões usadas pela aplicação
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql intl zip bcmath pcntl opcache \
    && apk add --no-cache fcgi tzdata mysql-client \
    && cp /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime \
    && echo "America/Sao_Paulo" > /etc/timezone

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-dem.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-dem.conf

WORKDIR /var/www/html

# -----------------------------------------------------------------------------
# dev: usado pelo docker-compose.yml local (código montado como volume)
# -----------------------------------------------------------------------------
FROM base AS dev

RUN install-php-extensions pdo_sqlite \
    && apk add --no-cache git unzip bash
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/dev-entrypoint.sh /usr/local/bin/dev-entrypoint
RUN chmod +x /usr/local/bin/dev-entrypoint \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/zz-dem.ini

ENTRYPOINT ["dev-entrypoint"]
CMD ["php-fpm"]

# -----------------------------------------------------------------------------
# vendor: dependências PHP de produção
# -----------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY app/ ./
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# -----------------------------------------------------------------------------
# assets: build do front-end (Tailwind + Alpine + service worker Workbox)
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION}-slim AS assets

WORKDIR /app
COPY app/package.json app/package-lock.json ./
RUN npm ci --ignore-scripts
COPY app/ ./
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

# -----------------------------------------------------------------------------
# prod: php-fpm + queue worker + scheduler sob supervisord
# -----------------------------------------------------------------------------
FROM base AS prod

ARG APP_VERSION=dev
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_VERSION=${APP_VERSION} \
    LOG_CHANNEL=stderr

RUN apk add --no-cache supervisor \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/zz-dem.ini

COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/php/prod-entrypoint.sh /usr/local/bin/prod-entrypoint

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Arquivos de implantação versionados junto com a imagem (extraídos pelo deploy no host)
COPY docker-compose.prod.yml docker-compose.prod.dedicado.yml /opt/dem-deploy/
COPY docker/nginx /opt/dem-deploy/nginx
COPY docker/mysql /opt/dem-deploy/mysql
COPY infra/server /opt/dem-deploy/server

RUN chmod +x /usr/local/bin/prod-entrypoint \
    && rm -rf tests node_modules .env \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

EXPOSE 9000
ENTRYPOINT ["prod-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
