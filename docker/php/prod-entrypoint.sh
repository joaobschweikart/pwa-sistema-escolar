#!/bin/sh
# Entrypoint de produção: publica os arquivos estáticos no volume compartilhado
# com o nginx e aquece os caches do Laravel. As migrações são executadas pelo
# deploy (infra/server/bin/deploy.sh), não aqui, para que o rollback seja seguro.
set -e
cd /var/www/html

if [ -d /var/www/public-shared ]; then
  # Copia a pasta public (inclui build Vite e service worker) para o nginx
  rm -rf /var/www/public-shared/build
  cp -a public/. /var/www/public-shared/
fi

if [ "${1:-}" = "supervisord" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
fi

exec "$@"
