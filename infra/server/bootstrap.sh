#!/usr/bin/env bash
# =============================================================================
# Bootstrap da instância Lightsail «dem-prod» (Ubuntu 24.04), executado uma vez
# como "launch script" (user-data). Os marcadores __VARIAVEL__ são preenchidos
# por infra/aws/02-provisionar.sh no momento da criação.
#   - swap de 1 GB, atualizações automáticas de segurança
#   - Docker Engine + Compose v2, AWS CLI v2, certbot
#   - agente SSM registrado por ATIVAÇÃO HÍBRIDA (Lightsail não é EC2)
#   - agente CloudWatch (modo onPremise; credenciais da função do SSM)
# =============================================================================
set -Eeuo pipefail
exec > >(tee -a /var/log/dem-bootstrap.log) 2>&1

REGIAO="__AWS_REGION__"
ATIVACAO_ID="__SSM_ACTIVATION_ID__"
ATIVACAO_CODIGO="__SSM_ACTIVATION_CODE__"
export DEBIAN_FRONTEND=noninteractive

echo "[bootstrap] início $(date -u)"
timedatectl set-timezone America/Sao_Paulo || true

# --- swap (2 GB de RAM com MySQL + PHP) ---
if ! swapon --show | grep -q /swapfile; then
  fallocate -l 1G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl -w vm.swappiness=10 && echo 'vm.swappiness=10' > /etc/sysctl.d/99-dem.conf
fi

# --- pacotes ---
apt-get update -y
apt-get install -y --no-install-recommends docker.io docker-compose-v2 certbot unzip curl jq cron unattended-upgrades
systemctl enable --now docker cron
dpkg-reconfigure -f noninteractive unattended-upgrades || true

# --- AWS CLI v2 ---
if ! command -v aws >/dev/null; then
  curl -fsSL "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o /tmp/awscliv2.zip
  unzip -q /tmp/awscliv2.zip -d /tmp && /tmp/aws/install && rm -rf /tmp/aws /tmp/awscliv2.zip
fi

# --- agente SSM via ativação híbrida ---
snap list amazon-ssm-agent >/dev/null 2>&1 && snap remove amazon-ssm-agent || true
curl -fsSL "https://amazon-ssm-${REGIAO}.s3.${REGIAO}.amazonaws.com/latest/linux_amd64/ssm-setup-cli" -o /tmp/ssm-setup-cli
chmod +x /tmp/ssm-setup-cli
/tmp/ssm-setup-cli -register -activation-code "$ATIVACAO_CODIGO" -activation-id "$ATIVACAO_ID" -region "$REGIAO"
rm -f /tmp/ssm-setup-cli
# O agente grava credenciais temporárias da função de serviço em /root/.aws/credentials

# --- agente CloudWatch (configurado a cada deploy com o arquivo versionado) ---
curl -fsSL "https://amazoncloudwatch-agent.s3.amazonaws.com/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb" -o /tmp/cwagent.deb
dpkg -i /tmp/cwagent.deb && rm -f /tmp/cwagent.deb
mkdir -p /opt/aws/amazon-cloudwatch-agent/etc
cat > /opt/aws/amazon-cloudwatch-agent/etc/common-config.toml <<TOML
[credentials]
  shared_credential_profile = "default"
  shared_credential_file = "/root/.aws/credentials"
TOML

# --- estrutura do DEM ---
mkdir -p /opt/dem /var/backups/dem /var/www/certbot
chmod 700 /opt/dem /var/backups/dem
touch /var/log/dem-deploy.log /var/log/dem-backup.log

echo "[bootstrap] concluído $(date -u)"
