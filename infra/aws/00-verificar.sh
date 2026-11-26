#!/usr/bin/env bash
# Confere AWS CLI, credenciais, conta e região antes de qualquer criação.
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
command -v aws >/dev/null || erro "instale o AWS CLI v2"
command -v jq >/dev/null || erro "instale o jq"
aws --version
conferir_conta
if aws iam get-account-summary --query 'SummaryMap.AccountMFAEnabled' --output text | grep -q 1; then
  ok "MFA ativo na conta raiz"
else
  aviso "MFA da conta raiz NÃO está ativo — ative antes de continuar"
fi
info "domínio: $DEM_DOMAIN · repositório: $GITHUB_REPO · bucket: $DEM_BUCKET"
