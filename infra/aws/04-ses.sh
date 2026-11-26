#!/usr/bin/env bash
# Amazon SES (recuperação de senha): verifica o domínio com DKIM, cria a
# credencial SMTP (porta 587, STARTTLS) e mostra como sair do sandbox.
# A credencial SMTP é a única chave estática do projeto: o protocolo SMTP
# exige usuário/senha; ela só permite ses:SendRawEmail a partir do domínio.
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"
conferir_conta
confirmar "Verificar ${DEM_DOMAIN} no SES (${AWS_REGION}) e criar o usuário SMTP dem-ses-smtp?"

if ! existe aws sesv2 get-email-identity --email-identity "$DEM_DOMAIN"; then
  aws sesv2 create-email-identity --email-identity "$DEM_DOMAIN" --tags Key=Projeto,Value=dem >/dev/null
fi
aws sesv2 get-email-identity --email-identity "$DEM_DOMAIN" --query 'DkimAttributes.Tokens' --output text | tr '\t' '\n' > "$SAIDA/ses-dkim.txt"
ok "identidade ${DEM_DOMAIN} criada. Registros DKIM (CNAME):"
while read -r t; do echo "    ${t}._domainkey.${DEM_DOMAIN}  CNAME  ${t}.dkim.amazonses.com"; done < "$SAIDA/ses-dkim.txt"
info "Se usar Lightsail DNS, rode ./03-dns.sh de novo para criá-los automaticamente."

if ! existe aws iam get-user --user-name dem-ses-smtp; then
  aws iam create-user --user-name dem-ses-smtp --tags Key=Projeto,Value=dem >/dev/null
fi
aws iam put-user-policy --user-name dem-ses-smtp --policy-name dem-ses-envio --policy-document "{
  \"Version\": \"2012-10-17\",
  \"Statement\": [{ \"Effect\": \"Allow\", \"Action\": \"ses:SendRawEmail\", \"Resource\": \"*\",
    \"Condition\": { \"StringLike\": { \"ses:FromAddress\": \"*@${DEM_DOMAIN}\" } } }]
}"

if [[ ! -f "$SAIDA/ses-smtp.env" ]]; then
  read -r CHAVE SEGREDO <<<"$(aws iam create-access-key --user-name dem-ses-smtp --query 'AccessKey.[AccessKeyId,SecretAccessKey]' --output text)"
  # Senha SMTP derivada da chave secreta (algoritmo documentado pela AWS)
  SENHA=$(SEGREDO="$SEGREDO" REGIAO="$AWS_REGION" python3 -c '
import base64, hashlib, hmac, os
def s(k, m): return hmac.new(k, m.encode(), hashlib.sha256).digest()
sig = s(("AWS4" + os.environ["SEGREDO"]).encode(), "11111111")
for parte in (os.environ["REGIAO"], "ses", "aws4_request", "SendRawEmail"):
    sig = s(sig, parte)
print(base64.b64encode(bytes([4]) + sig).decode())')
  umask 077
  printf 'MAIL_HOST=email-smtp.%s.amazonaws.com\nMAIL_USERNAME=%s\nMAIL_PASSWORD=%s\n' "$AWS_REGION" "$CHAVE" "$SENHA" > "$SAIDA/ses-smtp.env"
  ok "credencial SMTP salva em infra/out/ses-smtp.env (usada pelo 05-segredos.sh)"
fi

if [[ "$(aws sesv2 get-account --query ProductionAccessEnabled --output text)" != "True" ]]; then
  aviso "A conta SES está no SANDBOX: só envia para endereços verificados."
  cat <<SANDBOX
  Para sair do sandbox (análise da AWS em até 24 h), revise e execute:
    aws sesv2 put-account-details --production-access-enabled --mail-type TRANSACTIONAL \\
      --website-url https://${DEM_DOMAIN} --contact-language EN \\
      --use-case-description "Transactional password-reset e-mails for a municipal school diary (low volume, opt-in users only)."
  Enquanto isso, para testar, verifique o seu e-mail: aws sesv2 create-email-identity --email-identity ${ALERTA_EMAIL}
SANDBOX
else
  ok "conta SES fora do sandbox"
fi
