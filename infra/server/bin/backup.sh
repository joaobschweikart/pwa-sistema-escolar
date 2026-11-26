#!/usr/bin/env bash
# =============================================================================
# Backup diário do dem_db (RPO de 24 h): mysqldump consistente -> gzip ->
# bucket Lightsail «dem-backups», com retenção local e remota.
#   BACKUP_DESTINO=local  grava só em disco (usado no teste local)
# =============================================================================
set -Eeuo pipefail
DIR=${DEM_DIR:-/opt/dem}
CONTAINER=${DEM_MYSQL_CONTAINER:-dem-mysql}
PASTA=${BACKUP_PASTA:-/var/backups/dem}
RETENCAO_LOCAL=${BACKUP_RETENCAO_LOCAL_DIAS:-7}
log() { echo "[$(date -u +%FT%TZ)] [backup] $*"; }

if [[ -f "$DIR/.env" ]]; then
  set -a
  # shellcheck source=/dev/null
  source <(grep -E '^(BACKUP_|AWS_REGION)' "$DIR/.env")
  set +a
fi
if [[ -f "$DIR/deploy.conf" ]]; then
  # shellcheck source=/dev/null
  source "$DIR/deploy.conf"
fi
DESTINO=${BACKUP_DESTINO:-s3}
RETENCAO_REMOTA=${BACKUP_RETENCAO_DIAS:-30}

mkdir -p "$PASTA"
chmod 700 "$PASTA"
ARQUIVO="$PASTA/dem_db-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"

log "gerando dump em ${ARQUIVO}"
docker exec "$CONTAINER" sh -c 'exec mysqldump --single-transaction --quick --routines --triggers --no-tablespaces \
  -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" 2>/dev/null' | gzip -9 > "$ARQUIVO"
gzip -t "$ARQUIVO"
TAMANHO=$(stat -c %s "$ARQUIVO")
[[ "$TAMANHO" -gt 1024 ]] || { log "dump suspeito (${TAMANHO} bytes)"; exit 1; }
(cd "$PASTA" && sha256sum "$(basename "$ARQUIVO")" > "$(basename "$ARQUIVO").sha256")
log "dump ok (${TAMANHO} bytes)"

if [[ "$DESTINO" == "s3" ]]; then
  : "${BACKUP_BUCKET:?}" "${BACKUP_ACCESS_KEY_ID:?}" "${BACKUP_SECRET_ACCESS_KEY:?}"
  export AWS_ACCESS_KEY_ID="$BACKUP_ACCESS_KEY_ID" AWS_SECRET_ACCESS_KEY="$BACKUP_SECRET_ACCESS_KEY"
  export AWS_DEFAULT_REGION="${AWS_REGION:-sa-east-1}"
  aws s3 cp "$ARQUIVO" "s3://${BACKUP_BUCKET}/mysql/$(basename "$ARQUIVO")" --only-show-errors
  aws s3 cp "$ARQUIVO.sha256" "s3://${BACKUP_BUCKET}/mysql/$(basename "$ARQUIVO").sha256" --only-show-errors
  log "enviado para s3://${BACKUP_BUCKET}/mysql/"

  # Retenção remota: remove objetos mais antigos que RETENCAO_REMOTA dias
  LIMITE=$(date -u -d "-${RETENCAO_REMOTA} days" +%Y%m%d)
  aws s3 ls "s3://${BACKUP_BUCKET}/mysql/" | awk '{print $4}' | while read -r obj; do
    data=$(sed -nE 's/^dem_db-([0-9]{8})T.*/\1/p' <<<"$obj")
    if [[ -n "$data" && "$data" < "$LIMITE" ]]; then
      aws s3 rm "s3://${BACKUP_BUCKET}/mysql/${obj}" --only-show-errors && log "removido (retenção): ${obj}"
    fi
  done
fi

find "$PASTA" -name 'dem_db-*' -mtime "+${RETENCAO_LOCAL}" -delete
log "backup concluído"
