#!/usr/bin/env bash
# =============================================================================
# Deploy do DEM na instância «dem-prod» (executado via AWS SSM Run Command).
#   deploy.sh vX.Y.Z        implanta a tag (com healthcheck e rollback automático)
#   deploy.sh --rollback    volta para a tag anterior
# Variáveis esperadas (passadas pelo workflow cd.yml e gravadas em deploy.conf):
#   AWS_REGION, ECR_REGISTRY, DEM_DOMAIN, SSM_PARAM_PATH, CERTBOT_EMAIL
# =============================================================================
set -Eeuo pipefail

DIR=/opt/dem
COMPOSE=(docker compose --project-directory "$DIR" -f "$DIR/docker-compose.prod.yml")
LOG=/var/log/dem-deploy.log
exec > >(tee -a "$LOG") 2>&1
export HOME=${HOME:-/root}

log() { echo "[$(date -u +%FT%TZ)] [deploy] $*"; }

# Configuração persistente (para rollback manual e para o backup)
if [[ -n "${ECR_REGISTRY:-}" ]]; then
  umask 077
  cat > "$DIR/deploy.conf" <<CONF
AWS_REGION=${AWS_REGION}
ECR_REGISTRY=${ECR_REGISTRY}
DEM_DOMAIN=${DEM_DOMAIN}
SSM_PARAM_PATH=${SSM_PARAM_PATH:-/dem/prod}
CERTBOT_EMAIL=${CERTBOT_EMAIL:-}
CONF
fi
# shellcheck source=/dev/null
source "$DIR/deploy.conf"
export AWS_REGION AWS_DEFAULT_REGION="$AWS_REGION"
IMAGEM="${ECR_REGISTRY}/dem-app"

ATUAL=$(cat "$DIR/.dem-tag" 2>/dev/null || true)
ANTERIOR=$(cat "$DIR/.dem-tag-anterior" 2>/dev/null || true)

if [[ "${1:-}" == "--rollback" ]]; then
  [[ -n "$ANTERIOR" ]] || { log "não há tag anterior para rollback"; exit 1; }
  TAG="$ANTERIOR"
else
  TAG="${1:?uso: deploy.sh vX.Y.Z | --rollback}"
fi

gerar_env() {
  # Renderiza /opt/dem/.env a partir do Parameter Store (SecureString)
  local tag="$1" tmp
  tmp=$(mktemp)
  aws ssm get-parameters-by-path --path "$SSM_PARAM_PATH" --recursive --with-decryption \
    --query 'Parameters[*].[Name,Value]' --output text |
    while IFS=$'\t' read -r nome valor; do
      printf '%s="%s"\n' "${nome##*/}" "${valor//\"/\\\"}"
    done > "$tmp"
  {
    cat "$tmp"
    echo "APP_ENV=production"
    echo "APP_DEBUG=false"
    echo "APP_URL=https://${DEM_DOMAIN}"
    echo "DEM_DOMAIN=${DEM_DOMAIN}"
    echo "DEM_IMAGE=${IMAGEM}"
    echo "DEM_TAG=${tag}"
    echo "DB_CONNECTION=mysql"
    echo "DB_HOST=mysql"
    echo "DB_PORT=3306"
    echo "LOG_CHANNEL=stderr"
    echo "SESSION_SECURE_COOKIE=true"
  } > "$DIR/.env"
  chmod 600 "$DIR/.env"
  rm -f "$tmp"
}

saudavel() {
  local tag="$1" resposta
  for _ in $(seq 1 30); do
    resposta=$(curl -fsS --max-time 5 http://127.0.0.1/api/health 2>/dev/null || true)
    if grep -q '"status":"ok"' <<<"$resposta" && grep -q "\"versao\":\"${tag}\"" <<<"$resposta"; then
      log "healthcheck OK ($resposta)"
      return 0
    fi
    sleep 5
  done
  log "healthcheck FALHOU para ${tag}: ${resposta:-sem resposta}"
  return 1
}

subir() {
  local tag="$1"
  gerar_env "$tag"
  "${COMPOSE[@]}" pull --quiet
  "${COMPOSE[@]}" up -d --remove-orphans --wait --wait-timeout 180
}

log "iniciando deploy de ${TAG} (atual: ${ATUAL:-nenhuma})"
aws ecr get-login-password --region "$AWS_REGION" | docker login --username AWS --password-stdin "$ECR_REGISTRY" >/dev/null

if subir "$TAG" \
  && "${COMPOSE[@]}" exec -T php-fpm php artisan migrate --force \
  && saudavel "$TAG"; then
  if [[ "$TAG" != "$ATUAL" ]]; then
    [[ -n "$ATUAL" ]] && echo "$ATUAL" > "$DIR/.dem-tag-anterior"
    echo "$TAG" > "$DIR/.dem-tag"
  fi
  log "deploy de ${TAG} concluído"
else
  log "deploy de ${TAG} falhou"
  if [[ -n "$ATUAL" && "$ATUAL" != "$TAG" ]]; then
    log "ROLLBACK automático para ${ATUAL}"
    subir "$ATUAL" && saudavel "$ATUAL" && log "rollback para ${ATUAL} concluído"
  fi
  exit 1
fi

# Tarefas idempotentes do host: cron de backup, agente CloudWatch, TLS
install -m 0644 "$DIR/server/cron/dem" /etc/cron.d/dem
if [[ -x /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl ]]; then
  sed "s/\"region\": \"sa-east-1\"/\"region\": \"${AWS_REGION}\"/" "$DIR/server/cloudwatch/amazon-cloudwatch-agent.json" > "$DIR/cloudwatch-agent.json"
  /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a fetch-config -m onPremise -s \
    -c "file:$DIR/cloudwatch-agent.json" >/dev/null || log "aviso: agente CloudWatch não reconfigurado"
fi
if [[ ! -f "/etc/letsencrypt/live/${DEM_DOMAIN}/fullchain.pem" && -n "${CERTBOT_EMAIL:-}" ]]; then
  "$DIR/server/bin/certbot-init.sh" || log "aviso: certificado TLS ainda não emitido (verifique o DNS)"
fi

# Mantém somente as 3 imagens mais recentes da aplicação
docker images "$IMAGEM" --format '{{.Tag}}' | grep -v -e "^${TAG}$" -e "^${ANTERIOR:-__}$" | tail -n +2 |
  xargs -r -I{} docker rmi "${IMAGEM}:{}" >/dev/null 2>&1 || true
