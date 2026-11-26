#!/usr/bin/env bash
# =============================================================================
# Prepara uma instância Lightsail JÁ EXISTENTE (compartilhada com outra
# aplicação) para receber o DEM. Idempotente; não altera os containers nem o
# firewall da outra aplicação.
#   sudo SSM_ATIVACAO_ID=... SSM_ATIVACAO_CODIGO=... AWS_REGION=us-east-1 bash preparar-vm-compartilhada.sh
# =============================================================================
set -Eeuo pipefail
: "${AWS_REGION:?}" "${SSM_ATIVACAO_ID:?}" "${SSM_ATIVACAO_CODIGO:?}"
export DEBIAN_FRONTEND=noninteractive
log() { echo "[preparar] $*"; }

# --- swap de 2 GB (protege as duas aplicações de falta de memória) ---
if ! swapon --show | grep -q /swapfile; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl -q -w vm.swappiness=10 && echo 'vm.swappiness=10' > /etc/sysctl.d/99-dem.conf
  log "swap de 2 GB ativo"
else
  log "swap já existe"
fi

# --- utilitários ---
if ! command -v unzip >/dev/null || ! command -v jq >/dev/null; then
  apt-get install -y -qq unzip jq >/dev/null
fi

# --- AWS CLI v2 ---
if ! command -v aws >/dev/null; then
  curl -fsSL https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip -o /tmp/awscliv2.zip
  unzip -q -o /tmp/awscliv2.zip -d /tmp && /tmp/aws/install >/dev/null && rm -rf /tmp/aws /tmp/awscliv2.zip
  log "AWS CLI instalado: $(aws --version)"
fi

# --- agente SSM (snap) registrado por ativação híbrida ---
if [[ -f /var/snap/amazon-ssm-agent/common/registration ]] || grep -qs ManagedInstanceID /var/lib/amazon/ssm/registration; then
  log "agente SSM já registrado"
else
  snap stop amazon-ssm-agent >/dev/null
  /snap/amazon-ssm-agent/current/amazon-ssm-agent -register -y \
    -code "$SSM_ATIVACAO_CODIGO" -id "$SSM_ATIVACAO_ID" -region "$AWS_REGION" </dev/null
  snap start amazon-ssm-agent >/dev/null
  log "agente SSM registrado (nó híbrido)"
fi

# --- agente CloudWatch (configurado pelo deploy com o arquivo versionado) ---
if [[ ! -x /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl ]]; then
  curl -fsSL https://amazoncloudwatch-agent.s3.amazonaws.com/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb -o /tmp/cwagent.deb
  dpkg -i /tmp/cwagent.deb >/dev/null && rm -f /tmp/cwagent.deb
  log "agente CloudWatch instalado"
fi
mkdir -p /opt/aws/amazon-cloudwatch-agent/etc
cat > /opt/aws/amazon-cloudwatch-agent/etc/common-config.toml <<TOML
[credentials]
  shared_credential_profile = "default"
  shared_credential_file = "/root/.aws/credentials"
TOML

# --- estrutura do DEM ---
mkdir -p /opt/dem /var/backups/dem /var/log/dem/containers
chmod 700 /opt/dem /var/backups/dem
touch /var/log/dem-deploy.log /var/log/dem-backup.log
docker network inspect dem_borda >/dev/null 2>&1 </dev/null || docker network create dem_borda >/dev/null </dev/null
log "rede dem_borda e /opt/dem prontos"
free -m | sed -n '1,3p'
