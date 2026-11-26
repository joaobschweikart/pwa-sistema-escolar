#!/usr/bin/env bash
# Grava a configuração e os segredos de produção no SSM Parameter Store
# (SecureString, camada padrão gratuita). O deploy gera /opt/dem/.env a partir
# daqui: nenhum segredo fica no Git, na imagem ou no GitHub.
# Valores já existentes NÃO são sobrescritos (use --forcar para regenerar).
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
conferir_conta
FORCAR=${1:-}
[[ -f "$SAIDA/bucket-chave.json" ]] || erro "infra/out/bucket-chave.json não encontrado (rode 02-provisionar.sh)"
[[ -f "$SAIDA/ses-smtp.env" ]] || erro "infra/out/ses-smtp.env não encontrado (rode 04-ses.sh)"
# shellcheck source=/dev/null
source "$SAIDA/ses-smtp.env"
confirmar "Gravar os parâmetros em ${DEM_SSM_PARAM_PATH}/* ?"

aleatorio() { openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "$1"; }

gravar() {
  local nome="$1" valor="$2"
  if [[ "$FORCAR" != "--forcar" ]] && existe aws ssm get-parameter --name "${DEM_SSM_PARAM_PATH}/${nome}"; then
    echo "    = ${nome} (mantido)"; return
  fi
  aws ssm put-parameter --name "${DEM_SSM_PARAM_PATH}/${nome}" --type SecureString --value "$valor" --overwrite >/dev/null
  echo "    + ${nome}"
}

gravar APP_NAME "Diário Escolar Municipal"
gravar APP_KEY "base64:$(openssl rand -base64 32)"
gravar APP_LOCALE pt_BR
gravar APP_FALLBACK_LOCALE pt_BR
gravar APP_TIMEZONE America/Sao_Paulo
gravar DB_DATABASE dem_db
gravar DB_USERNAME dem
gravar DB_PASSWORD "$(aleatorio 32)"
gravar DB_ROOT_PASSWORD "$(aleatorio 32)"
gravar SESSION_DRIVER database
gravar SESSION_LIFETIME 480
gravar CACHE_STORE database
gravar QUEUE_CONNECTION database
gravar MAIL_MAILER smtp
gravar MAIL_SCHEME smtp
gravar MAIL_HOST "$MAIL_HOST"
gravar MAIL_PORT 587
gravar MAIL_USERNAME "$MAIL_USERNAME"
gravar MAIL_PASSWORD "$MAIL_PASSWORD"
gravar MAIL_FROM_ADDRESS "$MAIL_FROM_ADDRESS"
gravar MAIL_FROM_NAME "Diário Escolar Municipal"
gravar BACKUP_BUCKET "$DEM_BUCKET"
gravar BACKUP_ACCESS_KEY_ID "$(jq -r .accessKeyId "$SAIDA/bucket-chave.json")"
gravar BACKUP_SECRET_ACCESS_KEY "$(jq -r .secretAccessKey "$SAIDA/bucket-chave.json")"
gravar BACKUP_RETENCAO_DIAS "$BACKUP_RETENCAO_DIAS"
gravar AWS_REGION "$AWS_REGION"
ok "parâmetros gravados. Guarde infra/out/ fora do Git (já ignorado) ou apague após o deploy."
