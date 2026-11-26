# Infraestrutura como código — DEM

| Pasta | Conteúdo |
|---|---|
| `aws/` | provisionamento com AWS CLI (somente produção `dem-prod`), executado na máquina do administrador |
| `aws/politicas/` | políticas IAM (OIDC do GitHub, deploy, nó híbrido do SSM, ciclo de vida do ECR) |
| `server/bootstrap.sh` | *launch script* da instância Lightsail (Docker, agentes SSM/CloudWatch e certbot) |
| `server/bin/` | `deploy.sh` (healthcheck + rollback), `backup.sh`, `restore.sh`, `certbot-init.sh` |
| `server/cron/`, `server/cloudwatch/` | cron de backup/teste de restauração e configuração do agente CloudWatch |

Uso:

1. `cp config.env.example config.env` e preencha (o arquivo é ignorado pelo Git).
2. Rode os scripts de `aws/` na ordem numérica. Cada um mostra o que vai criar e pede confirmação.
3. Os arquivos gerados em `out/` (chave do bucket, IP) contêm segredos e também são
   ignorados pelo Git.

Os arquivos de `server/` entram na imagem `dem-app` e são extraídos para `/opt/dem` em cada deploy.
A descrição completa da infraestrutura está em [`../doc/infraestrutura.md`](../doc/infraestrutura.md).
