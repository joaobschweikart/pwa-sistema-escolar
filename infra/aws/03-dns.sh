#!/usr/bin/env bash
# DNS no Lightsail (zona gratuita): cria a zona e o registro A da aplicação.
# A API de DNS do Lightsail fica
# em us-east-1, independentemente da região da instância.
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
conferir_conta
[[ -f "$SAIDA/resultado.env" ]] || erro "rode 02-provisionar.sh antes"
# shellcheck source=/dev/null
source "$SAIDA/resultado.env"
DNS=(aws lightsail --region us-east-1)

confirmar "Criar a zona ${DEM_ZONA_DNS} no Lightsail DNS e o registro A ${DEM_DOMAIN} -> ${DEM_IP}?"

existe "${DNS[@]}" get-domain --domain-name "$DEM_ZONA_DNS" || "${DNS[@]}" create-domain --domain-name "$DEM_ZONA_DNS" >/dev/null
"${DNS[@]}" create-domain-entry --domain-name "$DEM_ZONA_DNS" \
  --domain-entry "name=${DEM_DOMAIN},type=A,target=${DEM_IP}" >/dev/null 2>&1 || aviso "registro A já existia"
ok "registro A ${DEM_DOMAIN} -> ${DEM_IP}"

info "Configure no registrador (ex.: Registro.br) os servidores DNS da zona:"
"${DNS[@]}" get-domain --domain-name "$DEM_ZONA_DNS" \
  --query "domain.domainEntries[?type=='NS'].target" --output text | tr '\t' '\n' | sed 's/^/    /'
