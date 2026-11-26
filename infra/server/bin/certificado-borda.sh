#!/usr/bin/env bash
# Modo compartilhado: emite o certificado Let's Encrypt de DEM_DOMAIN usando o
# container certbot da aplicação dona da borda (desafio HTTP-01 por webroot já
# servido pelo nginx de borda). A renovação acontece no laço "certbot renew" do
# próprio container, e o nginx de borda recarrega periodicamente.
set -Eeuo pipefail
DIR=/opt/dem
# shellcheck source=/dev/null
source "$DIR/deploy.conf"
CERTBOT=${DEM_BORDA_CERTBOT:-mestra_certbot}

if docker exec "$CERTBOT" test -f "/etc/letsencrypt/live/${DEM_DOMAIN}/fullchain.pem" </dev/null; then
  echo "[certificado] já existe para ${DEM_DOMAIN}"
  exit 0
fi

docker exec "$CERTBOT" certbot certonly --webroot -w /var/www/certbot -d "$DEM_DOMAIN" \
  --non-interactive --agree-tos --keep-until-expiring --register-unsafely-without-email </dev/null
echo "[certificado] emitido para ${DEM_DOMAIN}"
