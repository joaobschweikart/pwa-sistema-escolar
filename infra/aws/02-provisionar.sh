#!/usr/bin/env bash
# =============================================================================
# Provisiona SOMENTE o ambiente de produção «dem-prod» (homologação fica
# documentada como réplica reduzida, sem ser criada):
#   1. Amazon ECR «dem-app» (scan on push, tags imutáveis, ciclo de vida)
#   2. Lightsail Object Storage «dem-backups» + chave de acesso do backup
#   3. IAM: provedor OIDC do GitHub + role de deploy; role de serviço do SSM
#   4. SSM: ativação híbrida (Lightsail não é EC2)
#   5. Lightsail: instância 2 GB (bootstrap via launch script), IP estático,
#      firewall só 80/443, snapshots automáticos e alarmes
# Idempotente: recursos existentes são mantidos.
# =============================================================================
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
conferir_conta

TAGS_LS=("key=Projeto,value=dem" "key=Ambiente,value=producao")
DEM_MODO=${DEM_MODO:-dedicado}

if [[ "$DEM_MODO" == "compartilhado" ]]; then
  existe aws lightsail get-instance --instance-name "$DEM_INSTANCIA" || erro "instância ${DEM_INSTANCIA} não encontrada"
  cat <<RESUMO

Modo COMPARTILHADO: o DEM usará a instância existente ${DEM_INSTANCIA} (não será
criada instância, IP estático ou regra de firewall). Serão criados (${AWS_REGION}):
  • Bucket ${DEM_BUCKET} (plano 5 GB, ~US\$ 1/mês) · ECR ${DEM_ECR_REPO}
  • IAM: OIDC do GitHub (${GITHUB_REPO}) + roles dem-github-deploy e dem-ssm-hibrido
  • Ativação híbrida do SSM (1 registro, 24 h) para registrar ${DEM_INSTANCIA}
  • Alarmes (se ALERTA_EMAIL estiver definido)
RESUMO
else
  # Plano Lightsail de 2 GB (Linux, com IPv4) consultado na própria API
  PLANO=$(aws lightsail get-bundles --no-include-inactive --query \
    "bundles[?ramSizeInGb==\`2.0\` && isActive && contains(supportedPlatforms, 'LINUX_UNIX') && !contains(bundleId, 'ipv6')] | [0].[bundleId, price, cpuCount, diskSizeInGb, transferPerMonthInGb]" \
    --output text)
  read -r PLANO_ID PLANO_PRECO PLANO_CPU PLANO_DISCO PLANO_TRANSF <<<"$PLANO"
  SO=$(aws lightsail get-blueprints --query "blueprints[?starts_with(blueprintId, 'ubuntu_24') && isActive] | [0].blueprintId" --output text)
  cat <<RESUMO

Serão criados (região ${AWS_REGION}):
  • Lightsail ${DEM_INSTANCIA}: plano ${PLANO_ID} (${PLANO_CPU} vCPU, 2 GB, ${PLANO_DISCO} GB SSD, ${PLANO_TRANSF} GB/mês) — US\$ ${PLANO_PRECO}/mês · SO ${SO}
  • IP estático ${DEM_IP_ESTATICO} (gratuito enquanto anexado) · firewall: somente 80 e 443
  • Snapshots automáticos diários (cobrança por GB armazenado)
  • Bucket ${DEM_BUCKET} (plano 5 GB, ~US\$ 1/mês) · ECR ${DEM_ECR_REPO}
  • IAM: OIDC do GitHub (${GITHUB_REPO}) + roles dem-github-deploy e dem-ssm-hibrido
  • Ativação híbrida do SSM (1 registro, válida por 24 h)
RESUMO
fi
confirmar "Confirma a criação desses recursos na conta ${AWS_ACCOUNT_ID}?"

# ---------------------------------------------------------------- 1. ECR
if existe aws ecr describe-repositories --repository-names "$DEM_ECR_REPO"; then
  ok "ECR ${DEM_ECR_REPO} já existe"
else
  aws ecr create-repository --repository-name "$DEM_ECR_REPO" --image-tag-mutability IMMUTABLE \
    --image-scanning-configuration scanOnPush=true --encryption-configuration encryptionType=AES256 \
    --tags Key=Projeto,Value=dem >/dev/null
  aws ecr put-lifecycle-policy --repository-name "$DEM_ECR_REPO" \
    --lifecycle-policy-text "file://$(dirname "$0")/politicas/ecr-ciclo-de-vida.json" >/dev/null
  ok "ECR ${DEM_ECR_REPO} criado"
fi
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"

# ---------------------------------------------------------------- 2. bucket de backups
if existe aws lightsail get-bucket --bucket-name "$DEM_BUCKET"; then
  ok "bucket ${DEM_BUCKET} já existe"
else
  aws lightsail create-bucket --bucket-name "$DEM_BUCKET" --bundle-id small_1_0 --tags "${TAGS_LS[@]}" >/dev/null
  ok "bucket ${DEM_BUCKET} criado (acesso privado)"
fi
if [[ ! -f "$SAIDA/bucket-chave.json" ]]; then
  aws lightsail create-bucket-access-key --bucket-name "$DEM_BUCKET" --query accessKey > "$SAIDA/bucket-chave.json"
  chmod 600 "$SAIDA/bucket-chave.json"
  ok "chave de acesso do bucket salva em infra/out/bucket-chave.json (use no 05-segredos.sh)"
fi

# ---------------------------------------------------------------- 3. IAM
OIDC_ARN="arn:aws:iam::${AWS_ACCOUNT_ID}:oidc-provider/token.actions.githubusercontent.com"
if existe aws iam get-open-id-connect-provider --open-id-connect-provider-arn "$OIDC_ARN"; then
  ok "provedor OIDC do GitHub já existe"
else
  aws iam create-open-id-connect-provider --url https://token.actions.githubusercontent.com \
    --client-id-list sts.amazonaws.com --tags Key=Projeto,Value=dem >/dev/null
  ok "provedor OIDC do GitHub criado"
fi

if ! existe aws iam get-role --role-name dem-github-deploy; then
  aws iam create-role --role-name dem-github-deploy --description "Deploy do DEM pelo GitHub Actions (OIDC)" \
    --assume-role-policy-document "$(renderizar "$(dirname "$0")/politicas/github-confianca.json")" \
    --max-session-duration 3600 --tags Key=Projeto,Value=dem >/dev/null
fi
aws iam put-role-policy --role-name dem-github-deploy --policy-name dem-deploy \
  --policy-document "$(renderizar "$(dirname "$0")/politicas/github-permissoes.json")"
ok "role dem-github-deploy pronta"

if ! existe aws iam get-role --role-name dem-ssm-hibrido; then
  aws iam create-role --role-name dem-ssm-hibrido --description "Função de serviço do nó híbrido dem-prod" \
    --assume-role-policy-document "$(renderizar "$(dirname "$0")/politicas/ssm-confianca.json")" \
    --tags Key=Projeto,Value=dem >/dev/null
fi
for p in AmazonSSMManagedInstanceCore CloudWatchAgentServerPolicy AmazonEC2ContainerRegistryReadOnly; do
  aws iam attach-role-policy --role-name dem-ssm-hibrido --policy-arn "arn:aws:iam::aws:policy/${p}"
done
aws iam put-role-policy --role-name dem-ssm-hibrido --policy-name dem-parametros \
  --policy-document "$(renderizar "$(dirname "$0")/politicas/ssm-parametros.json")"
ok "role dem-ssm-hibrido pronta"

# ---------------------------------------------------------------- 4. ativação híbrida do SSM
criar_ativacao() {
  sleep 10 # propagação da role no IAM
  local expira
  expira=$(date -u -d '+24 hours' +%Y-%m-%dT%H:%M:%SZ)
  read -r ATIVACAO_ID ATIVACAO_CODIGO <<<"$(aws ssm create-activation --iam-role dem-ssm-hibrido \
    --default-instance-name "$DEM_INSTANCIA" --registration-limit 1 --expiration-date "$expira" \
    --description "Registro do Lightsail ${DEM_INSTANCIA} (DEM)" --tags Key=Projeto,Value=dem \
    --query '[ActivationId, ActivationCode]' --output text)"
  umask 077
  printf 'SSM_ATIVACAO_ID=%s\nSSM_ATIVACAO_CODIGO=%s\n' "$ATIVACAO_ID" "$ATIVACAO_CODIGO" > "$SAIDA/ssm-ativacao.env"
  ok "ativação híbrida criada (expira em ${expira}; dados em infra/out/ssm-ativacao.env)"
}

if [[ "$DEM_MODO" == "compartilhado" ]]; then
  if [[ "$(aws ssm describe-instance-information --filters "Key=tag:Projeto,Values=dem" --query 'length(InstanceInformationList)' --output text)" == "0" ]]; then
    criar_ativacao
    info "Registre o agente na instância com infra/server/preparar-vm-compartilhada.sh"
  else
    ok "nó híbrido do DEM já registrado no SSM"
  fi
elif existe aws lightsail get-instance --instance-name "$DEM_INSTANCIA"; then
  ok "instância ${DEM_INSTANCIA} já existe (ativação não necessária)"
else
  criar_ativacao

  # ------------------------------------------------------------ 5. instância Lightsail
  SCRIPT=$(sed -e "s|__AWS_REGION__|${AWS_REGION}|g" -e "s|__SSM_ACTIVATION_ID__|${ATIVACAO_ID}|g" \
    -e "s|__SSM_ACTIVATION_CODE__|${ATIVACAO_CODIGO}|g" "$RAIZ_INFRA/server/bootstrap.sh")
  ZONA="${AWS_REGION}a"
  aws lightsail create-instances --instance-names "$DEM_INSTANCIA" --availability-zone "$ZONA" \
    --blueprint-id "$SO" --bundle-id "$PLANO_ID" --user-data "$SCRIPT" --ip-address-type ipv4 \
    --tags "${TAGS_LS[@]}" \
    --add-ons "addOnType=AutoSnapshot,autoSnapshotAddOnRequest={snapshotTimeOfDay=09:00}" >/dev/null
  ok "instância ${DEM_INSTANCIA} criada em ${ZONA}; aguardando ficar 'running'..."
  for _ in $(seq 1 60); do
    [[ "$(aws lightsail get-instance-state --instance-name "$DEM_INSTANCIA" --query state.name --output text)" == "running" ]] && break
    sleep 10
  done
fi

if [[ "$DEM_MODO" == "compartilhado" ]]; then
  IP=$(aws lightsail get-instance --instance-name "$DEM_INSTANCIA" --query instance.publicIpAddress --output text)
  ok "instância compartilhada ${DEM_INSTANCIA} (${IP}); firewall e IP mantidos como estão"
else
  # IP estático
  if ! existe aws lightsail get-static-ip --static-ip-name "$DEM_IP_ESTATICO"; then
    aws lightsail allocate-static-ip --static-ip-name "$DEM_IP_ESTATICO" >/dev/null
  fi
  if [[ "$(aws lightsail get-static-ip --static-ip-name "$DEM_IP_ESTATICO" --query staticIp.isAttached --output text)" != "True" ]]; then
    aws lightsail attach-static-ip --static-ip-name "$DEM_IP_ESTATICO" --instance-name "$DEM_INSTANCIA" >/dev/null
  fi
  IP=$(aws lightsail get-static-ip --static-ip-name "$DEM_IP_ESTATICO" --query staticIp.ipAddress --output text)
  ok "IP estático ${IP} anexado"

  # Firewall: somente HTTP e HTTPS (sem SSH: o acesso operacional é pelo SSM)
  aws lightsail put-instance-public-ports --instance-name "$DEM_INSTANCIA" --port-infos \
    "fromPort=80,toPort=80,protocol=tcp" "fromPort=443,toPort=443,protocol=tcp" >/dev/null
  ok "firewall: portas 80 e 443"
fi

if [[ -n "${ALERTA_EMAIL:-}" ]]; then
  # Alarmes nativos do Lightsail (e-mail precisa ser confirmado uma vez)
  aws lightsail create-contact-method --protocol Email --contact-endpoint "$ALERTA_EMAIL" >/dev/null 2>&1 || true
  aws lightsail put-alarm --alarm-name dem-instancia-cpu --monitored-resource-name "$DEM_INSTANCIA" \
    --metric-name CPUUtilization --comparison-operator GreaterThanOrEqualToThreshold --threshold 80 \
    --evaluation-periods 3 --datapoints-to-alarm 3 --contact-protocols Email --notification-triggers ALARM >/dev/null
  aws lightsail put-alarm --alarm-name dem-instancia-status --monitored-resource-name "$DEM_INSTANCIA" \
    --metric-name StatusCheckFailed --comparison-operator GreaterThanOrEqualToThreshold --threshold 1 \
    --evaluation-periods 2 --datapoints-to-alarm 2 --contact-protocols Email --notification-triggers ALARM >/dev/null
  ok "alarmes Lightsail: CPU ≥ 80% e falha de status check"

  # Alarmes do CloudWatch sobre as métricas do agente (disco e memória) via SNS
  TOPICO=$(aws sns create-topic --name dem-alertas --tags Key=Projeto,Value=dem --query TopicArn --output text)
  aws sns subscribe --topic-arn "$TOPICO" --protocol email --notification-endpoint "$ALERTA_EMAIL" >/dev/null
  aws cloudwatch put-metric-alarm --alarm-name dem-prod-disco --namespace DEM/Producao --metric-name disk_used_percent \
    --dimensions Name=InstanceId,Value=dem-prod Name=path,Value=/ Name=fstype,Value=ext4 \
    --statistic Maximum --period 300 --evaluation-periods 2 --threshold 80 --comparison-operator GreaterThanOrEqualToThreshold \
    --treat-missing-data breaching --alarm-actions "$TOPICO"
  aws cloudwatch put-metric-alarm --alarm-name dem-prod-memoria --namespace DEM/Producao --metric-name mem_used_percent \
    --dimensions Name=InstanceId,Value=dem-prod --statistic Average --period 300 --evaluation-periods 3 --threshold 90 \
    --comparison-operator GreaterThanOrEqualToThreshold --alarm-actions "$TOPICO"
  aws logs put-metric-filter --log-group-name /dem/prod/backup --filter-name dem-backup-concluido \
    --filter-pattern '"backup concluído"' \
    --metric-transformations metricName=BackupConcluido,metricNamespace=DEM/Producao,metricValue=1,defaultValue=0 2>/dev/null \
    || aviso "grupo /dem/prod/backup ainda não existe: rode este script de novo após o primeiro deploy"
  aws cloudwatch put-metric-alarm --alarm-name dem-prod-backup-ausente --namespace DEM/Producao --metric-name BackupConcluido \
    --statistic Sum --period 86400 --evaluation-periods 1 --threshold 1 --comparison-operator LessThanThreshold \
    --treat-missing-data breaching --alarm-actions "$TOPICO"
  ok "alarmes CloudWatch: disco ≥ 80%, memória ≥ 90% e backup diário ausente (confirme a inscrição do e-mail no SNS)"
else
  aviso "ALERTA_EMAIL vazio: alarmes não criados (rode de novo após definir)"
fi

cat > "$SAIDA/resultado.env" <<RES
DEM_IP=${IP}
ECR_REGISTRY=${ECR_REGISTRY}
AWS_ROLE_ARN=arn:aws:iam::${AWS_ACCOUNT_ID}:role/dem-github-deploy
RES
cat <<FIM

Provisionamento concluído. Próximos passos:
  1) Aponte o DNS: registro A  ${DEM_DOMAIN} -> ${IP}   (ver 03-dns.sh)
  2) Em ~5 min confira o registro do SSM: ./06-ssm.sh status
  3) Rode ./05-segredos.sh
  4) GitHub → variables: AWS_ROLE_ARN, AWS_REGION, ECR_REGISTRY, SSM_INSTANCE_ID, DEM_DOMAIN, CERTBOT_EMAIL
     (valores em infra/out/resultado.env)
FIM
