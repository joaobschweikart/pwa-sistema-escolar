#!/bin/sh
# Entrypoint de desenvolvimento: prepara o projeto na primeira subida
# (dependências, .env, chave, migrações e seed de demonstração).
set -e
cd /var/www/html

if [ "${DEM_SKIP_SETUP:-0}" != "1" ]; then
  [ -f .env ] || cp .env.example .env
  [ -d vendor ] || composer install --no-interaction --prefer-dist
  grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

  echo "[dem] aguardando o MySQL..."
  i=0
  until php artisan db:show >/dev/null 2>&1; do
    i=$((i+1)); [ "$i" -gt 60 ] && { echo "[dem] MySQL indisponível"; exit 1; }
    sleep 2
  done

  php artisan migrate --force
  if [ "$(php artisan dem:usuarios-count 2>/dev/null || echo 0)" = "0" ]; then
    echo "[dem] banco vazio: carregando dados de demonstração"
    php artisan db:seed --force
  fi
  php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"
