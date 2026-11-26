#!/usr/bin/env bash
# Operação do nó híbrido do SSM.
#   ./06-ssm.sh status         mostra o ID mi-... e o estado do agente
#   ./06-ssm.sh testar         executa um comando de teste na instância
#   ./06-ssm.sh nova-ativacao  gera nova ativação (se a original expirou)
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
conferir_conta

case "${1:-status}" in
  status)
    aws ssm describe-instance-information --filters "Key=tag:Projeto,Values=dem" \
      --query 'InstanceInformationList[].[InstanceId,PingStatus,ComputerName,PlatformName,AgentVersion]' --output table
    ;;
  testar)
    ID=$(aws ssm describe-instance-information --filters "Key=tag:Projeto,Values=dem" --query 'InstanceInformationList[0].InstanceId' --output text)
    CMD=$(aws ssm send-command --instance-ids "$ID" --document-name AWS-RunShellScript \
      --parameters 'commands=["uptime","docker --version","docker compose version","tail -n 3 /var/log/dem-bootstrap.log"]' \
      --query Command.CommandId --output text)
    sleep 5
    aws ssm get-command-invocation --command-id "$CMD" --instance-id "$ID" --query '[Status,StandardOutputContent]' --output text
    ;;
  nova-ativacao)
    confirmar "Criar nova ativação híbrida (registro único, 24 h)?"
    read -r A_ID A_COD <<<"$(aws ssm create-activation --iam-role dem-ssm-hibrido --default-instance-name "$DEM_INSTANCIA" \
      --registration-limit 1 --expiration-date "$(date -u -d '+24 hours' +%Y-%m-%dT%H:%M:%SZ)" \
      --tags Key=Projeto,Value=dem --query '[ActivationId, ActivationCode]' --output text)"
    cat <<INSTR
Abra temporariamente o SSH somente para o console do Lightsail:
  aws lightsail open-instance-public-ports --instance-name ${DEM_INSTANCIA} \\
    --port-info fromPort=22,toPort=22,protocol=TCP,cidrListAliases=lightsail-connect
Conecte pelo navegador (console Lightsail > ${DEM_INSTANCIA} > Conectar) e execute:
  curl -fsSL https://amazon-ssm-${AWS_REGION}.s3.${AWS_REGION}.amazonaws.com/latest/linux_amd64/ssm-setup-cli -o ssm-setup-cli
  chmod +x ssm-setup-cli && sudo ./ssm-setup-cli -register -activation-code "${A_COD}" -activation-id "${A_ID}" -region "${AWS_REGION}"
Depois feche a porta 22:
  aws lightsail close-instance-public-ports --instance-name ${DEM_INSTANCIA} --port-info fromPort=22,toPort=22,protocol=TCP
INSTR
    ;;
  *) erro "uso: $0 status|testar|nova-ativacao" ;;
esac
