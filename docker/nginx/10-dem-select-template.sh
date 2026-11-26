#!/bin/sh
# Escolhe o template do nginx: HTTPS se o certificado do domínio já existe,
# senão HTTP (permite emitir o certificado pelo desafio ACME webroot).
set -e
mkdir -p /etc/nginx/templates /var/www/certbot
rm -f /etc/nginx/conf.d/default.conf /etc/nginx/templates/*.template
if [ -n "${DEM_DOMAIN}" ] && [ -f "/etc/letsencrypt/live/${DEM_DOMAIN}/fullchain.pem" ]; then
  echo "[dem] certificado encontrado: modo HTTPS"
  cp /opt/dem-nginx/templates/https.conf.template /etc/nginx/templates/dem.conf.template
else
  echo "[dem] sem certificado: modo HTTP"
  cp /opt/dem-nginx/templates/http.conf.template /etc/nginx/templates/dem.conf.template
fi
