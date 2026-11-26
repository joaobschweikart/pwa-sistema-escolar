#!/usr/bin/env bash
# =============================================================================
# Restauração do dem_db a partir de um dump do backup.sh.
#   restore.sh --teste [arquivo|ultimo]   restaura num banco temporário,
#                                         compara contagens e apaga (não afeta produção)
#   restore.sh --producao arquivo|ultimo  restaura sobre o dem_db (pede confirmação)
# "ultimo" baixa o dump mais recente do bucket (ou usa o mais recente local).
# =============================================================================
set -Eeuo pipefail
DIR=${DEM_DIR:-/opt/dem}
CONTAINER=${DEM_MYSQL_CONTAINER:-dem-mysql}
PASTA=${BACKUP_PASTA:-/var/backups/dem}
log() { echo "[$(date -u +%FT%TZ)] [restore] $*"; }

MODO=${1:?uso: restore.sh --teste|--producao [arquivo|ultimo]}
ORIGEM=${2:-ultimo}

if [[ -f "$DIR/.env" ]]; then
  set -a
  # shellcheck source=/dev/null
  source <(grep -E '^(BACKUP_|AWS_REGION)' "$DIR/.env")
  set +a
fi

obter_arquivo() {
  if [[ "$ORIGEM" != "ultimo" ]]; then echo "$ORIGEM"; return; fi
  if [[ "${BACKUP_DESTINO:-s3}" == "s3" && -n "${BACKUP_BUCKET:-}" ]]; then
    export AWS_ACCESS_KEY_ID="$BACKUP_ACCESS_KEY_ID" AWS_SECRET_ACCESS_KEY="$BACKUP_SECRET_ACCESS_KEY"
    export AWS_DEFAULT_REGION="${AWS_REGION:-sa-east-1}"
    local obj
    obj=$(aws s3 ls "s3://${BACKUP_BUCKET}/mysql/" | awk '{print $4}' | grep -E '\.sql\.gz$' | sort | tail -n1)
    [[ -n "$obj" ]] || { log "nenhum backup no bucket"; exit 1; }
    mkdir -p "$PASTA"
    aws s3 cp "s3://${BACKUP_BUCKET}/mysql/${obj}" "$PASTA/${obj}" --only-show-errors
    echo "$PASTA/${obj}"
  else
    find "$PASTA" -maxdepth 1 -name 'dem_db-*.sql.gz' -printf '%T@ %p\n' | sort -n | tail -n1 | cut -d' ' -f2-
  fi
}

ARQUIVO=$(obter_arquivo)
[[ -f "$ARQUIVO" ]] || { log "arquivo não encontrado: $ARQUIVO"; exit 1; }
gzip -t "$ARQUIVO"
if [[ -f "$ARQUIVO.sha256" ]]; then (cd "$(dirname "$ARQUIVO")" && sha256sum -c "$(basename "$ARQUIVO").sha256" >/dev/null) && log "checksum ok"; fi
log "usando ${ARQUIVO}"

mysql_root() { docker exec -i "$CONTAINER" sh -c "exec mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" $* 2>/dev/null"; }
contar() {
  echo "SELECT CONCAT_WS(',', (SELECT COUNT(*) FROM $1.users), (SELECT COUNT(*) FROM $1.alunos),
        (SELECT COUNT(*) FROM $1.aulas), (SELECT COUNT(*) FROM $1.frequencias), (SELECT COUNT(*) FROM $1.notas),
        (SELECT COUNT(*) FROM $1.auditoria_notas));" | mysql_root -N
}

case "$MODO" in
  --teste)
    INICIO=$(date +%s)
    BANCO=dem_db_restore_teste
    echo "DROP DATABASE IF EXISTS ${BANCO}; CREATE DATABASE ${BANCO} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql_root
    gunzip -c "$ARQUIVO" | mysql_root "$BANCO"
    RESTAURADO=$(contar "$BANCO")
    PRODUCAO=$(docker exec "$CONTAINER" sh -c 'echo "$MYSQL_DATABASE"')
    ATUAL=$(contar "$PRODUCAO")
    echo "DROP DATABASE ${BANCO};" | mysql_root
    log "contagens (users,alunos,aulas,frequencias,notas,auditoria) restaurado=${RESTAURADO} producao=${ATUAL}"
    log "restauração de teste concluída em $(( $(date +%s) - INICIO ))s"
    [[ -n "$RESTAURADO" ]] || { log "FALHA: banco restaurado vazio"; exit 1; }
    ;;
  --producao)
    read -r -p "Isto SUBSTITUI o banco de produção pelo backup ${ARQUIVO}. Digite RESTAURAR para continuar: " ok
    [[ "$ok" == "RESTAURAR" ]] || { log "cancelado"; exit 1; }
    PRODUCAO=$(docker exec "$CONTAINER" sh -c 'echo "$MYSQL_DATABASE"')
    gunzip -c "$ARQUIVO" | mysql_root "$PRODUCAO"
    log "banco ${PRODUCAO} restaurado; contagens: $(contar "$PRODUCAO")"
    ;;
  *) echo "modo inválido: $MODO"; exit 1 ;;
esac
