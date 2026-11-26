#!/usr/bin/env bash
# Funções comuns dos scripts de provisionamento do DEM.
set -Eeuo pipefail

RAIZ_INFRA="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONFIG="${DEM_CONFIG:-$RAIZ_INFRA/config.env}"
[[ -f "$CONFIG" ]] || { echo "Arquivo $CONFIG não encontrado. Copie infra/config.env.example para infra/config.env e preencha."; exit 1; }
# shellcheck source=/dev/null
source "$CONFIG"

export AWS_REGION AWS_DEFAULT_REGION="$AWS_REGION"
[[ -n "${AWS_PROFILE:-}" ]] && export AWS_PROFILE
export AWS_PAGER=""
SAIDA="$RAIZ_INFRA/out"
mkdir -p "$SAIDA"
chmod 700 "$SAIDA"

info()  { printf '\033[1;34m[dem]\033[0m %s\n' "$*"; }
ok()    { printf '\033[1;32m[ok]\033[0m %s\n' "$*"; }
aviso() { printf '\033[1;33m[aviso]\033[0m %s\n' "$*"; }
erro()  { printf '\033[1;31m[erro]\033[0m %s\n' "$*" >&2; exit 1; }

# Pede confirmação antes de criar recursos (DEM_SIM=1 pula a pergunta)
confirmar() {
  [[ "${DEM_SIM:-0}" == "1" ]] && return 0
  read -r -p "$1 [s/N] " resposta
  [[ "$resposta" =~ ^[sS]$ ]] || { info "cancelado"; exit 0; }
}

conferir_conta() {
  local conta
  conta=$(aws sts get-caller-identity --query Account --output text) || erro "AWS CLI sem credenciais (rode aws configure)"
  [[ "$conta" == "$AWS_ACCOUNT_ID" ]] || erro "conta autenticada ($conta) difere de AWS_ACCOUNT_ID ($AWS_ACCOUNT_ID)"
  ok "conta $conta · região $AWS_REGION"
}

existe() { "$@" >/dev/null 2>&1; }

# Substitui __VARIAVEL__ pelos valores do ambiente
renderizar() {
  local conteudo
  conteudo=$(cat "$1")
  for v in AWS_ACCOUNT_ID AWS_REGION GITHUB_REPO DEM_ECR_REPO DEM_SSM_PARAM_PATH; do
    conteudo=${conteudo//__${v}__/${!v}}
  done
  printf '%s' "$conteudo"
}
