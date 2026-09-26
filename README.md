# Diário Escolar Municipal (DEM) — Modelagem Arquitetural de um PWA

**Práticas Extensionistas IV** · Análise e Desenvolvimento de Sistemas · UNOESC

**Acadêmico:** João Vitor Bernardon Schweikart

## Problema

Escolas municipais do interior têm dificuldade de acesso a sistemas escolares para lançar
o diário de classe e as notas dos alunos. O obstáculo não é só a falta de um sistema: é a
**dependência de conexão contínua**. A internet dessas escolas é instável ou inexistente
justamente na sala de aula, que é onde o registro deveria acontecer. Na prática, o
professor anota em papel e transcreve depois — com retrabalho, atraso e risco de erro.

## Objetivo

Modelar a arquitetura de um **PWA (Progressive Web App)** capaz de operar **sem conexão**
durante a aula e de **sincronizar** os lançamentos quando a rede voltar, com custo e
esforço de manutenção compatíveis com uma secretaria municipal de pequeno porte.

O produto desta atividade é a **modelagem arquitetural**, não a implementação do sistema.

## Solução proposta

O **Diário Escolar Municipal (DEM)** é um **monolito modular em Laravel** que entrega ao
navegador uma aplicação web progressiva:

- o professor instala o PWA pelo próprio navegador, sem loja de aplicativos;
- durante a aula, o *service worker* serve a interface a partir do cache e os lançamentos
  ficam em uma fila local no **IndexedDB**;
- com conexão, essa fila é enviada em **lotes idempotentes** para a API, que os aplica no
  banco relacional;
- o servidor roda em uma **única instância Amazon Lightsail**, com os containers de
  servidor web, aplicação e banco MySQL.
