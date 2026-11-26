# Configuração da infraestrutura em nuvem — Diário Escolar Municipal (DEM)

**Práticas Extensionistas IV · Entrega 2** · Análise e Desenvolvimento de Sistemas · UNOESC
**Acadêmico:** João Vitor Bernardon Schweikart
**Repositório:** <https://github.com/joaobschweikart/pwa-sistema-escolar>

Este documento descreve a infraestrutura de produção do DEM como ela está **implementada no código**
deste repositório. Toda a infraestrutura é definida como código: o provisionamento usa AWS CLI
(`infra/aws/`), os servidores usam Docker Compose (`docker-compose.prod.yml`) e a entrega contínua
usa GitHub Actions (`.github/workflows/`). Os nomes de recursos são os mesmos da modelagem aprovada:
`dem-app`, `dem_db`, `dem-prod` e `dem-backups`.

![Diagrama de implantação](diagramas/02-implantacao.png)

---

## 1. Resumo da solução

| Item | Escolha |
|---|---|
| Provedor | Amazon Web Services (AWS) |
| Região | `sa-east-1` (São Paulo), zona `sa-east-1a` |
| Modelo de serviço | IaaS simplificado (Amazon Lightsail) combinado com serviços gerenciados (PaaS/SaaS) |
| Computação | 1 instância Lightsail `dem-prod`: Ubuntu 24.04, 2 vCPU, 2 GB RAM, 60 GB SSD |
| Execução | Docker Compose com 3 containers: `nginx`, `php-fpm` (imagem `dem-app`) e `mysql` (banco `dem_db`) |
| Imagens | Amazon ECR, repositório `dem-app` (tags imutáveis) |
| Deploy | GitHub Actions → OIDC → ECR → aprovação manual → AWS Systems Manager (Run Command) |
| Segredos | AWS Systems Manager Parameter Store (`SecureString`) |
| Backup | `mysqldump` diário → Lightsail Object Storage `dem-backups` + snapshot diário da instância |
| E-mail | Amazon SES via SMTP (porta 587, STARTTLS) |
| Monitoramento | Amazon CloudWatch (logs e métricas do agente), alarmes do Lightsail e AWS Budgets |
| Custo estimado | cerca de **US$ 15 a 17 por mês** (seção 10) |
| Objetivos de recuperação | **RTO de 4 h** e **RPO de 24 h** |

Escopo desta entrega: provisionamos **somente produção** (`dem-prod`). A homologação está documentada
como réplica reduzida (seção 12), mas não foi criada.

---

## 2. Modelo de serviço: IaaS, PaaS e SaaS

A solução combina os três modelos de serviço, cada um onde ele faz mais sentido.

| Camada | Serviço | Modelo | O que fica com a equipe do município |
|---|---|---|---|
| Servidor da aplicação | Amazon Lightsail (instância) | **IaaS** | SO, Docker, containers, atualizações e backup do banco |
| Registro de imagens | Amazon ECR | PaaS | somente as imagens publicadas |
| Execução remota do deploy | AWS Systems Manager | PaaS | os scripts de deploy |
| Segredos | SSM Parameter Store | PaaS | os valores dos parâmetros |
| Armazenamento de backups | Lightsail Object Storage (S3) | PaaS | política de retenção |
| E-mail transacional | Amazon SES | PaaS/SaaS | domínio e conteúdo dos e-mails |
| Logs, métricas e alarmes | Amazon CloudWatch, Budgets | SaaS | limites e destinatários dos alertas |
| Código e CI/CD | GitHub e GitHub Actions | SaaS | workflows e revisões |
| Produto final | DEM para as escolas | **SaaS** do ponto de vista da Secretaria e das escolas | — |

### Por que Lightsail (IaaS) e não EC2 + RDS, Fargate ou Beanstalk

Para uma secretaria municipal de pequeno porte, o fator decisivo é **custo previsível e baixo esforço
de operação**, não o desempenho de pico (capítulo 6 da modelagem).

- **Preço fixo mensal** com transferência de dados, IP estático, DNS e firewall incluídos no mesmo console.
- **Carga modesta e concentrada:** até 30 escolas, 400 professores e 9.000 alunos por município. Uma
  instância de 2 GB atende com folga (seção 11).
- A **PWA funciona offline**, então uma indisponibilidade do servidor adia a sincronização, mas não
  impede o registro em sala. Isso torna aceitável um único nó, sem alta disponibilidade nativa.
- **Alternativas descartadas:**
  - **EC2 + RDS + ALB**: exige VPC, sub-redes e grupos de segurança, com custo mínimo maior (só um RDS
    gerenciado já supera o custo total previsto).
  - **ECS/Fargate**: elasticidade que a carga não exige, cobrança por vCPU/hora e um load balancer pago.
  - **Elastic Beanstalk / App Runner**: menos controle sobre o MySQL local e custo maior.
  - **Servidor na própria escola (self-hosted)**: é exatamente o que falha hoje (energia, internet e
    manutenção). Não oferece backup externo nem TLS público confiável.
- **Caminho de saída:** a AWS permite exportar snapshots do Lightsail para o EC2. A migração não exige
  reescrever a aplicação (seção 11).

O banco roda em container na própria instância, com **custo adicional zero**, latência desprezível e
o ambiente local idêntico ao de produção. O lado negativo é que o risco fica concentrado em um nó e o
backup fica sob responsabilidade da equipe. Isso é tratado nas seções 8 e 9. O banco gerenciado do
Lightsail parte de US$ 15/mês, o que dobraria o custo sem ganho perceptível para este volume.

---

## 3. Região: `sa-east-1` (São Paulo)

- **Latência menor** para as escolas brasileiras (sincronização de lotes e uso online da gestão).
- **Residência de dados no Brasil:** os dados pessoais de crianças e adolescentes ficam em território
  nacional, o que facilita a conformidade com a LGPD e com as exigências de órgãos públicos.
- Todos os serviços usados estão disponíveis na região: Lightsail, ECR, SSM, CloudWatch, SES e Budgets.
  A exceção é a **API de DNS do Lightsail**, que por definição da AWS opera em `us-east-1`. Ela só
  guarda os registros DNS, nenhum dado da aplicação.
- Os preços em São Paulo costumam ser maiores que nos EUA. O script de provisionamento consulta o preço
  real do plano na API (`aws lightsail get-bundles`) e o mostra antes de pedir confirmação.

A região é um parâmetro (`AWS_REGION` em `infra/config.env`), assim como a conta e o domínio.

---

## 4. Recursos e configurações

### 4.1 Instância Lightsail `dem-prod`

| Configuração | Valor |
|---|---|
| Plano | 2 GB RAM, 2 vCPU, 60 GB SSD, 3 TB de transferência/mês, IPv4 (escolhido pela API em `02-provisionar.sh`) |
| Sistema | Ubuntu 24.04 LTS, fuso `America/Sao_Paulo`, *unattended-upgrades* ativo |
| Memória | swap de 1 GB, `vm.swappiness=10` |
| IP | IP estático `dem-prod-ip` (gratuito enquanto anexado) |
| Firewall | somente **TCP 80 e 443** abertos; **porta 22 fechada** |
| Snapshots | snapshot automático diário às 09:00 UTC (06:00 de Brasília); o Lightsail mantém os 7 mais recentes |
| Bootstrap | `infra/server/bootstrap.sh`, executado como *launch script* na criação: instala Docker Engine + Compose v2, AWS CLI v2, certbot, agente SSM (ativação híbrida) e agente CloudWatch |
| Diretório da aplicação | `/opt/dem` (permissão 700): compose, configurações do nginx/MySQL, scripts e `.env` gerado no deploy |

### 4.2 Containers (`docker-compose.prod.yml`)

| Container | Imagem | Função | Limite de memória | Rede |
|---|---|---|---|---|
| `dem-nginx` | `nginx:stable-alpine` | TLS (Let's Encrypt), HTTP/2, arquivos estáticos (Vite, service worker), cabeçalhos de segurança, gzip | 96 MB | `app` (portas 80/443 publicadas) |
| `dem-php-fpm` | `dem-app:vX.Y.Z` (ECR) | Laravel 13 / PHP 8.5 sob **supervisord**: `php-fpm` + `queue:work` + `schedule:work` | 768 MB | `app` + `dados` |
| `dem-mysql` | `mysql:8.4` (LTS) | banco `dem_db` | 768 MB | **somente `dados`** (rede interna) |

- **Imagem `dem-app`** (`Dockerfile` multi-stage, `linux/amd64`): `base` (PHP-FPM + extensões) →
  `vendor` (Composer sem dependências de desenvolvimento, autoload otimizado) → `assets` (build Vite +
  Workbox) → `prod` (supervisord, OPcache sem revalidação, healthcheck FastCGI `/ping`). A imagem
  também carrega os arquivos de implantação (`/opt/dem-deploy`), então **o artefato versionado é um só**.
- **PHP-FPM:** `pm=dynamic`, até 12 processos, `pm.max_requests=500` e `memory_limit=256M`, dimensionados
  para caber nos 2 GB junto com o MySQL.
- **MySQL** (`docker/mysql/my.cnf`): `utf8mb4`, `innodb_buffer_pool_size=384M`, `max_connections=60`,
  `performance_schema=OFF`.
- **Logs** dos containers: driver `json-file` com rotação (5 × 10 MB), coletados pelo agente CloudWatch.
- **Healthchecks:** nginx (`/api/health`), php-fpm (FastCGI `/ping`), mysql (`mysqladmin ping`). Todos
  os containers usam `restart: unless-stopped`.

### 4.3 Serviços de apoio

| Recurso | Nome | Configuração |
|---|---|---|
| Amazon ECR | `dem-app` | tags **imutáveis**, *scan on push*, criptografia AES-256, ciclo de vida mantendo as 15 imagens mais recentes (o rollback é só uma troca de tag) |
| Lightsail Object Storage | `dem-backups` | plano de 5 GB, acesso **privado**, chave de acesso exclusiva do backup |
| SSM Parameter Store | `/dem/prod/*` | ~26 parâmetros `SecureString` (KMS), camada padrão gratuita (lista em `infra/aws/05-segredos.sh`) |
| IAM: role `dem-github-deploy` | — | assumida pelo GitHub Actions via **OIDC**. Só pode publicar no repositório `dem-app` e enviar comandos SSM para nós com a tag `Projeto=dem` |
| IAM: role `dem-ssm-hibrido` | — | função do nó híbrido: `AmazonSSMManagedInstanceCore`, `CloudWatchAgentServerPolicy`, leitura do ECR e leitura de `/dem/prod/*` |
| SSM: ativação híbrida | `dem-prod` | 1 registro, validade de 24 h, tag `Projeto=dem` (o Lightsail não é EC2 e por isso não usa *instance profile*) |
| Amazon SES | domínio da aplicação | identidade de domínio com DKIM, usuário SMTP `dem-ses-smtp` restrito a `ses:SendRawEmail` com remetente `*@domínio` |
| DNS | Lightsail DNS (zona gratuita) | registro A `domínio → IP estático` e CNAMEs de DKIM |
| CloudWatch | `/dem/prod/{containers,deploy,backup}` | retenção de 30/90/90 dias; métricas `DEM/Producao` (memória e disco) |
| AWS Budgets | `dem-mensal` | limite de US$ 20/mês; alertas em 80% do gasto real e 100% do previsto |

### 4.4 Configuração da aplicação em produção

O `.env` de produção **não existe no Git nem na imagem**. A cada deploy, `deploy.sh` gera
`/opt/dem/.env` (permissão 600) a partir do Parameter Store e acrescenta `APP_ENV=production`,
`APP_DEBUG=false`, `APP_URL=https://<domínio>`, `SESSION_SECURE_COOKIE=true` e a versão implantada.
Sessão, cache e fila usam o próprio MySQL (`database`), sem Redis, para economizar memória.

---

## 5. Rede, portas e TLS

```
Dispositivo do professor ──HTTPS 443──▶ [Firewall Lightsail: 80, 443]
                                              │
                                     dem-nginx (TLS 1.2/1.3, HSTS)
                                              │ FastCGI 9000 (rede "app")
                                      dem-php-fpm ──SMTP 587 (STARTTLS)──▶ Amazon SES
                                              │ MySQL 3306 (rede "dados", interna)
                                         dem-mysql   (sem porta publicada, sem saída à internet)

Host dem-prod ──HTTPS 443 (somente saída)──▶ SSM · ECR · CloudWatch · Parameter Store · bucket dem-backups
```

| Porta | Origem → destino | Uso |
|---|---|---|
| 443/TCP | internet → nginx | aplicação, API e PWA (HTTPS) |
| 80/TCP | internet → nginx | desafio ACME do Let's Encrypt, redirecionamento 301 para HTTPS e `/api/health` |
| 9000/TCP | nginx → php-fpm | FastCGI, somente na rede Docker `app` |
| 3306/TCP | php-fpm → mysql | somente na rede Docker `dados` (`internal: true`) |
| 587/TCP | php-fpm → SES | e-mail com STARTTLS |
| 443/TCP (saída) | host → AWS | agente SSM, ECR (pull), CloudWatch, Parameter Store, S3 (backup) |
| 22/TCP | — | **fechada**. A operação e o deploy usam o canal de saída do agente SSM. Em emergência, `06-ssm.sh` abre a porta 22 só para o console do Lightsail (`lightsail-connect`) |

**TLS:** o certificado vem do **Let's Encrypt** pelo desafio HTTP-01 (*webroot* servido pelo nginx).
`infra/server/bin/certbot-init.sh` roda automaticamente no primeiro deploy, assim que o DNS aponta para
a instância. O container do nginx escolhe sozinho o modo HTTP (antes do certificado) ou HTTPS (depois).
A renovação usa o *timer* do certbot no host, com um hook que recarrega o nginx. A configuração usa
TLS 1.2/1.3, HTTP/2, `Strict-Transport-Security` de 1 ano, `X-Frame-Options`, `X-Content-Type-Options`,
`Referrer-Policy` e `Permissions-Policy`. O service worker tem cabeçalho `Service-Worker-Allowed: /` e
não é guardado em cache HTTP.

---

## 6. Segurança e LGPD

### 6.1 Controles técnicos

- **Identidades e acesso à AWS:** MFA na conta raiz, usuário IAM para o dia a dia e roles de privilégio
  mínimo. **O GitHub não guarda chaves da AWS**, porque autentica por OIDC com escopo restrito às tags
  `v*` e ao environment `production`.
- **Chaves estáticas:** só existem duas, ambas inevitáveis e restritas. A credencial SMTP do SES (o
  protocolo SMTP exige usuário e senha) só envia e-mail do próprio domínio. A chave do bucket só acessa
  `dem-backups`. As duas ficam no Parameter Store.
- **Segredos:** guardados no Parameter Store como `SecureString`, criptografados com KMS. Os scripts nunca
  gravam segredos no Git: `infra/config.env` e `infra/out/` estão no `.gitignore`.
- **Superfície de ataque:** somente 80/443 abertas, banco sem porta publicada e em rede sem saída, sem
  SSH e com atualizações automáticas de segurança no SO.
- **Aplicação:**
  - autorização por papel (Admin da Secretaria, Gestor Escolar e Professor) e por vínculo
    professor↔turma/disciplina, com Policies e regras de domínio cobertas por testes;
  - senhas com bcrypt e redefinição por token que expira em 60 minutos;
  - *rate limit* de 120 req/min na API, 30 lotes de sync/min e 5 tentativas de login/min;
  - CSRF nas telas web, cookies de sessão `Secure`/`HttpOnly` e tokens Sanctum com validade de 30 dias,
    revogados no logout;
  - documentação da API restrita a gestores em produção.
- **Integridade das notas:** cada alteração gera um registro **imutável** de auditoria (quem, quando,
  valor anterior e novo, origem). Um conflito de nota nunca é aplicado automaticamente: a decisão é do
  gestor.
- **Dispositivo do professor:** o cache guarda somente as turmas do próprio professor. O IndexedDB e o
  Cache Storage são **apagados no logout** (verificado no teste E2E). Se outro professor entrar no mesmo
  aparelho, o cache de turmas do anterior é descartado.
- **Backups:** trafegam por HTTPS, ficam criptografados em repouso no S3, têm retenção de 30 dias e o
  arquivo leva um checksum SHA-256.

### 6.2 LGPD (Lei nº 13.709/2018)

- **Controlador:** Secretaria Municipal de Educação. **Operador:** a AWS, como provedora de nuvem.
  A Secretaria deve indicar um **encarregado (DPO)**.
- **Base legal:** execução de políticas públicas e cumprimento de obrigação legal pela administração
  pública (art. 7º, II e III; art. 23). Os dados de crianças e adolescentes são tratados no seu
  **melhor interesse** (art. 14), apenas para a finalidade escolar.
- **Minimização:** o sistema guarda somente o necessário ao diário: nome, data de nascimento, código do
  aluno, responsável, frequência e notas. Não há dados sensíveis de saúde nem biometria.
- **Residência:** dados e backups ficam em `sa-east-1` (Brasil).
- **Segregação:** cada escola só enxerga seus dados, e cada professor só vê as próprias turmas.
- **Rastreabilidade:** auditoria de notas, logs de deploy e de backup no CloudWatch.
- **Ciclo de vida:** retenção de backups de 30 dias. A guarda de longo prazo do registro escolar segue a
  norma educacional e é feita pelo próprio banco.
- **Plano gratuito da AWS:** use-o somente na validação acadêmica. Antes de inserir dados reais de
  alunos, mude a conta para o plano pago (seção 10).

---

## 7. CI/CD

![Diagrama DevOps](diagramas/03-devops.png)

**Versionamento:** Git Flow simplificado. Branches `feature/*` saem de `develop` com commits
convencionais; a promoção para `main` acontece por *pull request*; a publicação é feita por tag
semântica `vX.Y.Z`.

### 7.1 Integração contínua (`.github/workflows/ci.yml`)

Roda em todo *pull request* para `develop` ou `main` (e em push para `develop`):

1. **Pint** (estilo de código), **Larastan nível 7** (análise estática) e **Pest** (testes de unidade,
   de feature e de arquitetura, com 94 testes);
2. **Build Vite + Workbox**, conferindo que o service worker e o manifest foram gerados;
3. **Build da imagem Docker** `dem-app` (target `prod`, `linux/amd64`, com cache);
4. **E2E Playwright**: sobe o ambiente com `docker compose up --wait` e roda os testes offline em desktop
   e celular.

Se alguma etapa falhar, o merge fica bloqueado. Configure a proteção de branch exigindo o CI.

### 7.2 Entrega contínua (`.github/workflows/cd.yml`)

Disparada por tag `vX.Y.Z`:

1. **OIDC → role `dem-github-deploy`** (sem chaves estáticas).
2. **Build e push** da imagem `dem-app:vX.Y.Z` no ECR, com a versão embutida (`APP_VERSION`).
3. **Environment `production` com aprovação manual.** O responsável da Secretaria aprova no GitHub,
   porque o sistema guarda registro acadêmico oficial.
4. **`aws ssm send-command` (AWS-RunShellScript)** na instância: `docker pull`, extração dos arquivos de
   implantação de dentro da imagem e execução de `infra/server/bin/deploy.sh vX.Y.Z`, que:
   - gera o `.env` a partir do Parameter Store;
   - roda **`docker compose pull && up -d --wait`**;
   - roda **`php artisan migrate --force`**;
   - faz o **healthcheck** em `/api/health`, conferindo `status=ok` e `versao=vX.Y.Z` (até 150 s);
   - se algo falhar, faz **rollback automático** para a tag anterior (a imagem antiga continua no ECR;
     não há novo build);
   - mantém, de forma idempotente, o cron de backup, a configuração do agente CloudWatch e o TLS.
5. **Healthcheck externo** em HTTPS pelo GitHub Actions. Se falhar, o workflow envia
   `deploy.sh --rollback` via SSM.

**Migrações:** são *forward-only* e devem ser compatíveis com a versão anterior (padrão
*expand/contract*), para que o rollback de imagem seja seguro.

---

## 8. Backup e recuperação (RTO 4 h / RPO 24 h)

| Camada | Mecanismo | Frequência | Retenção | Destino |
|---|---|---|---|---|
| Banco `dem_db` | `infra/server/bin/backup.sh`: `mysqldump --single-transaction` → gzip → SHA-256 | diário, 03:15 (cron em `/etc/cron.d/dem`) | 30 dias no bucket, 7 dias no disco | bucket `dem-backups` |
| Instância inteira | snapshot automático do Lightsail | diário, 06:00 | 7 snapshots | Lightsail |
| Aplicação | imagens imutáveis no ECR | a cada tag | 15 versões | ECR |
| Configuração | Parameter Store + código no Git | a cada alteração | histórico de versões | AWS / GitHub |
| Lançamentos offline | fila no IndexedDB do professor | até sincronizar | até o envio | dispositivo |

- **RPO de 24 h:** no pior caso perdem-se os lançamentos do dia que já tinham sincronizado. Na prática a
  perda é menor, porque o que ainda estava offline continua na fila dos aparelhos e é reenviado (os lotes
  são idempotentes).
- **Teste de restauração:**
  - `restore.sh --teste ultimo` restaura o último dump num banco temporário, compara as contagens das
    tabelas principais com produção e apaga o banco temporário;
  - roda **todo mês** pelo cron e o resultado vai para o CloudWatch;
  - foi validado localmente nesta entrega: contagens restauradas iguais às de produção em cerca de 2 s;
  - um alarme avisa se o backup diário não for concluído.

**Procedimento de recuperação (meta: 4 h)**

| Cenário | Ação | Tempo estimado |
|---|---|---|
| Container travado | `restart: unless-stopped` e healthchecks reiniciam automaticamente | minutos |
| Versão com defeito | rollback automático, ou `deploy.sh --rollback` via SSM | < 10 min |
| Banco corrompido | `restore.sh --producao ultimo` (pede confirmação) | < 30 min |
| Perda da instância | 1) criar instância a partir do último snapshot (`create-instances-from-snapshot`) ou do zero com `02-provisionar.sh`; 2) reanexar o IP estático (o DNS não muda); 3) registrar o SSM; 4) rodar o workflow CD com a tag atual; 5) `restore.sh --producao ultimo` se o snapshot for mais antigo que o dump | 1 a 2 h |
| Indisponibilidade da região | recriar em outra região com os mesmos scripts (`AWS_REGION`), restaurando o dump copiado do bucket | até 4 h |

Durante toda a recuperação **as aulas continuam sendo registradas offline**.

---

## 9. Monitoramento

| O que | Como | Alerta |
|---|---|---|
| Saúde da aplicação | `GET /api/health` (banco, versão, tempo) usado pelo Docker, pelo deploy e pelo CD | rollback automático; e-mail do GitHub em falha |
| CPU e *status check* da instância | alarmes nativos do Lightsail | e-mail: CPU ≥ 80% por 15 min, falha de status |
| Memória e disco | agente CloudWatch (namespace `DEM/Producao`) | SNS por e-mail: disco ≥ 80%, memória ≥ 90% |
| Logs dos containers | CloudWatch Logs `/dem/prod/containers` (30 dias) | consulta no Logs Insights |
| Deploys e backups | `/dem/prod/deploy`, `/dem/prod/backup` (90 dias) | alarme "backup diário ausente" |
| Custos | AWS Budgets `dem-mensal` | e-mail em 80% (real) e 100% (previsto) |
| Erros funcionais | fila de conflitos e auditoria de notas no painel do gestor | indicador no painel |

Recomendação complementar sem custo: um monitor externo de disponibilidade (por exemplo, UptimeRobot)
apontado para `https://<domínio>/api/health`. Incidentes e retornos das escolas viram *issues* no
GitHub, o que fecha o ciclo DevOps da modelagem.

---

## 10. Custos (estimativa mensal, região São Paulo)

| Item | Estimativa (US$/mês) | Observação |
|---|---|---|
| Lightsail 2 GB (IPv4) | ~12,00 | preço fixo; o script mostra o valor da API antes de criar |
| IP estático | 0,00 | gratuito enquanto anexado |
| Snapshots automáticos | ~1,00 a 2,00 | US$ 0,05/GB-mês, incrementais |
| Bucket `dem-backups` (5 GB) | 1,00 | dumps compactados ocupam poucos MB |
| Amazon ECR | ~0,10 a 0,30 | US$ 0,10/GB-mês; ciclo de vida limita a 15 imagens |
| CloudWatch | ~0,60 a 1,00 | 2 métricas personalizadas, alarmes e poucos GB de log |
| SES | < 0,10 | US$ 0,10 por mil e-mails (só recuperação de senha) |
| SSM (Run Command, ativação padrão, Parameter Store padrão) | 0,00 | camada gratuita |
| Lightsail DNS | 0,00 | zona incluída |
| Domínio | ~0,70 | ex.: `.com.br` no Registro.br, cerca de R$ 40/ano |
| **Total** | **~15 a 17** | o orçamento `dem-mensal` alerta acima de US$ 20 |

**Plano gratuito:** contas AWS novas entram no *Free plan*, com créditos de boas-vindas válidos por até
6 meses e utilizáveis em serviços elegíveis, como o Lightsail. Ao fim do período, ou quando os créditos
acabam, a conta é **encerrada** se não for convertida para o plano pago. Por isso a recomendação da
modelagem continua valendo: use a gratuidade apenas na validação acadêmica, configure o AWS Budgets
logo no início e migre para o plano pago antes de inserir dados reais. Confira as condições vigentes em
<https://aws.amazon.com/free/> e <https://aws.amazon.com/lightsail/pricing/>.

---

## 11. Escalabilidade e migração para EC2

**Capacidade atual:** no pico (início das aulas), 400 professores sincronizam lotes pequenos (dezenas de
operações, cerca de 50 a 300 ms cada). Mesmo que todos sincronizassem no mesmo minuto, seriam algumas
requisições por segundo, bem dentro de 12 processos PHP-FPM. Os dados de um município (9.000 alunos,
cerca de 200 dias letivos de frequência) somam poucos GB no MySQL.

A aplicação está pronta para escalar horizontalmente:

- **sem estado no servidor:** sessão, cache e fila ficam no banco, e os arquivos estáticos são
  versionados;
- **API idempotente:** reenvios de lotes são seguros.

**Degraus de crescimento:**

1. **Vertical no Lightsail:** snapshot → nova instância de 4 ou 8 GB → mover o IP estático (minutos de
   janela, sem mudar o DNS).
2. **Separar o banco:** banco gerenciado do Lightsail ou Amazon RDS (MySQL 8), bastando alterar `DB_HOST`
   no Parameter Store. Isso traz backups automáticos e *Multi-AZ*.
3. **Migrar para EC2:** `aws lightsail export-snapshot` gera uma AMI/EBS no EC2. Depois:
   - **ALB** e **Auto Scaling Group** com a mesma imagem `dem-app`, ou **ECS**;
   - **RDS Multi-AZ**;
   - **CloudFront** para os estáticos e o service worker;
   - **ElastiCache/SQS** se a fila crescer.

   O pipeline continua o mesmo (OIDC, ECR, aprovação manual). Muda só o destino do deploy, que passa a
   ser o SSM em EC2 (o *instance profile* substitui a ativação híbrida) ou o ECS.
4. **Vários municípios:** uma instalação por município (isolamento de dados, como na modelagem) com os
   mesmos scripts parametrizados por `infra/config.env`.

---

## 12. Homologação (planejada, não provisionada)

Réplica reduzida da produção, descrita aqui para orientar a próxima etapa:

- instância Lightsail de 1 GB (`dem-hml`, cerca de US$ 7/mês), sem snapshots automáticos;
- mesmos scripts, com `DEM_INSTANCIA=dem-hml`, `DEM_SSM_PARAM_PATH=/dem/hml` e um subdomínio próprio;
- deploy automático a cada merge em `main`, como environment `staging` sem aprovação;
- dados fictícios (`DemoSeeder`), nunca dados reais de alunos.

O diagrama DevOps (`doc/diagramas/03-devops.puml`) marca essa etapa como planejada.

---

## 13. Como provisionar (resumo)

Pré-requisitos: AWS CLI v2 configurado, `jq`, `openssl` e `python3`. Copie
`infra/config.env.example` para `infra/config.env` e preencha a conta, a região, o domínio, o e-mail e o
repositório. Todos os scripts **pedem confirmação** antes de criar recursos e são idempotentes.

```bash
cd infra/aws
./00-verificar.sh      # credenciais, conta, região e MFA da raiz
./01-orcamento.sh      # AWS Budgets
./02-provisionar.sh    # ECR, bucket, IAM/OIDC, ativação SSM, Lightsail, IP, firewall, snapshots e alarmes
./03-dns.sh            # zona Lightsail DNS + registro A (+ DKIM)
./04-ses.sh            # domínio no SES + credencial SMTP (+ instruções para sair do sandbox)
./05-segredos.sh       # Parameter Store (/dem/prod/*)
./06-ssm.sh status     # confere o nó híbrido (ID mi-...)
```

Em seguida, configure as *variables* do GitHub (`AWS_ROLE_ARN`, `AWS_REGION`, `ECR_REGISTRY`,
`SSM_INSTANCE_ID`, `DEM_DOMAIN`, `CERTBOT_EMAIL`) e o environment `production` com revisor obrigatório.
Depois publique a primeira tag: `git tag v1.0.0 && git push origin v1.0.0`.
