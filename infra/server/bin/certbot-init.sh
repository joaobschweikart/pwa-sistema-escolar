#!/usr/bin/env bash
# Emite o certificado Let's Encrypt (desafio HTTP-01 via webroot servido pelo
# nginx) e recarrega o nginx em modo HTTPS. A renovação é feita pelo timer do
# certbot, que executa o hook de deploy abaixo.
set -Eeuo pipefail
DIR=/opt/dem
# shellcheck source=/dev/null
source "$DIR/deploy.conf"
: "${DEM_DOMAIN:?}" "${CERTBOT_EMAIL:?defina CERTBOT_EMAIL}"

mkdir -p /var/www/certbot /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/dem-nginx-reload.sh <<'HOOK'
#!/bin/sh
docker exec dem-nginx nginx -s reload
HOOK
chmod +x /etc/letsencrypt/renewal-hooks/deploy/dem-nginx-reload.sh

certbot certonly --webroot -w /var/www/certbot -d "$DEM_DOMAIN" \
  --email "$CERTBOT_EMAIL" --agree-tos --no-eff-email --non-interactive --keep-until-expiring

# Reinicia o nginx para trocar o template HTTP pelo HTTPS
docker compose --project-directory "$DIR" -f "$DIR/docker-compose.prod.yml" up -d --force-recreate --no-deps nginx
echo "[certbot] certificado ativo para ${DEM_DOMAIN}"
