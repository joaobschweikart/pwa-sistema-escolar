#!/usr/bin/env bash
# Cria o orçamento mensal no AWS Budgets com alertas por e-mail
# (80% do valor real e 100% do valor previsto).
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
conferir_conta
confirmar "Criar orçamento 'dem-mensal' de US\$ ${ORCAMENTO_USD}/mês com alertas para ${ALERTA_EMAIL}?"

if existe aws budgets describe-budget --account-id "$AWS_ACCOUNT_ID" --budget-name dem-mensal; then
  ok "orçamento dem-mensal já existe"; exit 0
fi

aws budgets create-budget --account-id "$AWS_ACCOUNT_ID" \
  --budget "{\"BudgetName\":\"dem-mensal\",\"BudgetLimit\":{\"Amount\":\"${ORCAMENTO_USD}\",\"Unit\":\"USD\"},\"TimeUnit\":\"MONTHLY\",\"BudgetType\":\"COST\"}" \
  --notifications-with-subscribers "[
    {\"Notification\":{\"NotificationType\":\"ACTUAL\",\"ComparisonOperator\":\"GREATER_THAN\",\"Threshold\":80,\"ThresholdType\":\"PERCENTAGE\"},
     \"Subscribers\":[{\"SubscriptionType\":\"EMAIL\",\"Address\":\"${ALERTA_EMAIL}\"}]},
    {\"Notification\":{\"NotificationType\":\"FORECASTED\",\"ComparisonOperator\":\"GREATER_THAN\",\"Threshold\":100,\"ThresholdType\":\"PERCENTAGE\"},
     \"Subscribers\":[{\"SubscriptionType\":\"EMAIL\",\"Address\":\"${ALERTA_EMAIL}\"}]}
  ]"
ok "orçamento criado"
