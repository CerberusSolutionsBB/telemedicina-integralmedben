# Análise do sistema MENDBEM e do módulo Financeiro

> Data: 13/10/2026 · Branch: `feature/financeiro-f0` · Base: `main` em `dacf156`
>
> Documento de diagnóstico. O desenho da solução está em [implementacao.md](implementacao.md) e o plano de execução em [desenvolvimento.md](desenvolvimento.md).

---

## 1. Visão geral

| Item | Situação |
|------|----------|
| Stack | PHP 8.4, Laravel 12.70, Inertia v2 + Vue 3, Tailwind, Pest 3 |
| Multi-tenancy | `stancl/tenancy` 3.10, **um banco por tenant** (parceiro) + banco central `telemedicinamedben` |
| Permissões | `spatie/laravel-permission` 7.4 (ACL própria em `routes/acl.php`) |
| Auditoria | `owen-it/laravel-auditing` 14 — usada também como **fonte de dados de negócio** (evento `registro_plano`) |
| Integrações | SIPROV (associados/benefícios), Zoug (SMS), Clube (benefícios), WhatsApp |
| Volume do código | 236 rotas, 32 models, 87 services, 29 arquivos de teste |
| Ambiente de dev | `php artisan serve` + MariaDB local (`127.0.0.1`); stack Docker/Podman opcional |

**Domínios centrais**

- **Central (admin MENDBEM):** Páginas de Parceiros (`Pagina/*`), Dashboard, Financeiro, Formulários dinâmicos, SIPROV, Templates SMS, ACL.
- **Tenant (parceiro):** Beneficiários (pacientes), dependentes/familiares, formulários públicos, Desempenho (metas e comissões), Configuração.

**Modelo de planos e vagas (base do financeiro)**

- `tenant_planos`: por tenant e plano, guarda `quantidade` contratada, `saldo`, `valor` (mensalidade) e comissão (`comissao_tipo`, que é percentual ou fixo, e `comissao_valor`).
- `tenants_quantidade_parceiros`: extrato de vagas por movimento (`consumo`, `devolucao`, `ajuste`, `zeragem`), com o `valor` do plano no momento do consumo ou da devolução.
- `audits` com evento `registro_plano`: registra cada venda (vínculo de beneficiário a plano) com `valor` e `comissao` do momento da venda e o vendedor.
- `TenantPlanoCotaService` concentra consumo e devolução com `lockForUpdate`. O desenho é bom e está testado (`TenantPlanoSaldoTest`).

---

## 2. Achados críticos (P0): corrigir antes de qualquer feature

### 2.1 Rotas do painel central sem autenticação 🔴

`RouteModuleServiceProvider` registra os módulos `pagina`, `configuracao` e `form` só com o middleware `web`, sem `auth`. Dentro de `routes/pagina.php`, só algumas rotas recebem `->middleware('auth')` individualmente. Em `routes/web.php`, as rotas `tenants.store`, `tenants.update` e `tenants.destroy` ficam **fora** do grupo `auth`.

`php artisan route:list` mostra **49 rotas sem `auth`**. Fora as que são públicas de propósito (login, formulário público `f/{slug}`), as mais graves são:

| Rota | Efeito sem login |
|------|------------------|
| `GET pagina/{tenant}` | Página do parceiro com a **lista de beneficiários (nome, CPF, e-mail, telefone)** |
| `DELETE pagina/{tenant}` | **Exclui o parceiro** (domínios, arquivos, detalhes) |
| `POST store` / `PUT tenants/{tenant}` / `DELETE delete` | Cria, altera ou exclui tenant (o `TenantStoreRequest::authorize()` retorna `true`) |
| `GET/POST/PUT/DELETE pagina/users/{tenant}/*` | Gerencia usuários do parceiro |
| `GET/POST/PUT/DELETE configuracoes/credencias_cluble/*` | Gerencia credenciais da integração Clube |
| `PUT pagina/configuracao/{tenant}/*`, `PUT pagina/bulk/disable` | Altera configuração e status dos parceiros |
| `GET pagina/configuracao/siprov/search` | Consulta à SIPROV |

**Confirmado no ambiente local:** um `GET /pagina/med_lima` anônimo retornou 200 com 5 CPFs no payload Inertia, e `GET /configuracoes/credencias_cluble` retornou 200. O CSRF só bloqueia ataques vindos de outro site: qualquer visitante obtém o token na landing page e consegue chamar as rotas de escrita. É vazamento de dado pessoal (LGPD) e risco de destruição de dados.

### 2.2 Falta de autorização (além da autenticação)

- Só `acl.php`, `form.php` e `siprov.php` usam `permission:`. As rotas em `pagina.php` (37), `web.php` (27), `tenant.php` (47), `configuracao.php`, `lei.php` e `admin.php` dependem apenas de estar logado. Qualquer usuário central autenticado, de qualquer perfil, acessa o Financeiro e exclui parceiros.
- As rotas `forms/sms-templates/*` editam e excluem templates de qualquer tenant sem checagem de permissão.

### 2.3 Segredos e dados sensíveis

- `DOCS/dados.md` está **versionado no git** com URL, usuário e senha de um painel de revenda (`envio.devyx.com.br/reseller`). É preciso remover o arquivo do repositório, trocar a senha e considerar limpar o histórico.
- `storage/logs/browser.log` gravou a **senha digitada no login** (o aviso do Vue inclui o `modelValue` do campo `password`). O envio de logs do navegador precisa mascarar campos sensíveis.
- 34 pontos em controllers devolvem `$e->getMessage()` ao cliente (por exemplo `SmsTemplateController`, `Pagina*`, `Lei*`), expondo SQL e caminhos internos.

---

## 3. Qualidade e arquitetura

| # | Achado | Impacto |
|---|--------|---------|
| Q1 | Arquivos gigantes: `Pagina/Show.vue` (2.154 linhas), `Siprov/Index.vue` (1.120), `Tenant/Configuracao/Index.vue` (1.058), `PublicFormController.php` (1.272), `ConfiguracaoController.php` (840) | Difícil de revisar e testar; conflitos de merge frequentes |
| Q2 | Duas pastas de services com papéis sobrepostos: `app/Services` e `app/Http/Services` | Sem critério claro de onde criar código novo |
| Q3 | O tenant atual é obtido de formas diferentes: `tenant()`, `str($host)->before('.')` (`FormShowController`) ou `tenant_id` vindo do request | Risco de ler dados do tenant errado |
| Q4 | Chamadas `fetch` com `X-XSRF-TOKEN` montado à mão (4 arquivos) em vez de `router`/`useForm` do Inertia | Tratamento de erro e de sessão expirada inconsistente |
| Q5 | Código morto ou comentado: verificação de telefone por SMS, `enviarSmsTemplate` antigo, rota `/teste` em `routes/tenant.php` | Ruído e superfície de ataque |
| Q6 | A auditoria é usada como fonte de dados de negócio (histórico de planos, vendas, comissões) e os filtros rodam em PHP sobre até 2.000 registros (`PlanoHistoricoService::VARREDURA`) | Não escala e é frágil, já que `new_values` é texto JSON |
| Q7 | `CHANGELOG.md` parou na branch `feature/parceiros-status-siprov-cartao` (julho) | A documentação não acompanha as entregas |
| Q8 | `TenantStoreRequest::authorize()` retorna `true` e há rotas de tenant duplicadas em `web.php` e `admin.php` | Duas portas para a mesma operação, uma delas aberta (ver 2.1) |

---

## 4. Performance

- **Dashboard e Financeiro centrais:** `DashboardService::getData()` percorre todos os tenants com `$tenant->run()` (troca de banco) e chama `TenantPlanoCotaService::uso()` por tenant a cada requisição. Com 9 tenants funciona; com centenas, a página fica lenta. Cabe guardar um snapshot ou cache por competência.
- **Histórico de planos:** os filtros de busca e de plano são aplicados em PHP sobre `audits` (ver Q6).
- **Testes:** `RefreshDatabase` roda todas as migrations no MariaDB local a cada processo, e cada DDL leva segundos. Um único arquivo de teste de autenticação passa de 90 segundos. A suíte completa não termina em 10 minutos. Isso desestimula rodar testes (ver [desenvolvimento.md §4](desenvolvimento.md#4-testes)).

---

## 5. Testes

- 29 arquivos (26 Feature e 3 Unit). A cobertura é boa nos serviços de domínio: cota/saldo, `PacientePlanoService`, `DesempenhoService`, histórico, dashboard do tenant, importação, SIPROV.
- **Sem testes de autorização:** nenhum teste garante que as rotas exigem login ou permissão, e foi assim que o problema 2.1 passou despercebido.
- `phpunit.xml` tem uma alteração local não commitada (remoção de `DB_CONNECTION=mysql`).
- Resultado da suíte nesta análise: ver o [anexo A](#anexo-a--resultado-da-suíte-de-testes).

---

## 6. Módulo Financeiro: estado atual

### 6.1 O que existe

**Tela central `Financeiro`** (`/financiero`, `FinancieroController` e `Pages/Financiero/Index.vue`):

- Reaproveita `DashboardService::getData()` inteiro. Não existe um serviço financeiro próprio, e a página recebe dados que não usa.
- Filtros por parceiro e plano, aplicados no front.
- KPIs **reais:**
  - **MRR** = vidas em uso × valor **atual** do plano.
  - **Vidas ativas / contratadas** e inativos.
  - **Desempenho por parceiro**: receita, vidas e cadastros (top 5).
- KPIs e blocos **vazios (`—`)**: Margem por vida, Churn mensal, Inadimplência, A pagar, Uso e custo da telemedicina, Sinistralidade.

**Dados financeiros já gravados:**

| Dado | Onde | Observação |
|------|------|------------|
| Mensalidade do plano por parceiro | `tenant_planos.valor` | Valor atual; alterações ficam só na auditoria |
| Regra de comissão | `tenant_planos.comissao_tipo/comissao_valor` + `TenantPlano::comissaoVenda()` | Percentual ou fixo |
| Valor no consumo e na devolução de vaga | `tenants_quantidade_parceiros.valor` | Snapshot por movimento |
| Venda (valor + comissão + vendedor) | `audits` (`registro_plano`) | Snapshot, mas guardado na auditoria |
| Metas de comissão | `desempenhos` (banco do tenant), `meta_valor` | `DesempenhoService::progressoComissao()` |
| Comissões no dashboard do tenant | `TenantDashboardService::comissoes()` | Total, ticket, ranking por vendedor, por plano |

### 6.2 Lacunas

1. **Sem faturamento:** não existe fatura, competência (mês de referência), vencimento nem status de pagamento. O "MRR" é uma estimativa, não receita faturada.
2. **Sem recebimentos:** sem registro de pagamento não há como calcular inadimplência.
3. **Sem custos:** nenhum lugar guarda o custo da telemedicina (por vida ou por consulta). Margem e sinistralidade não têm como ser calculadas.
4. **Comissão sem controle de pagamento:** a comissão é calculada a partir da auditoria, mas não existe registro do que está a pagar, aprovado ou pago. Por isso o bloco "A pagar" está vazio.
5. **O MRR usa o preço atual:** quando a mensalidade muda, a receita dos meses anteriores é recalculada errado.
6. **Churn sem definição:** os movimentos de `devolucao` e as inativações de beneficiário existem, mas não há uma regra formal (base de vidas no início do mês etc.).
7. **Lógica duplicada:** `comissaoDoRegistro()` está copiada em `DesempenhoService` e em `TenantDashboardService`.
8. **Sem permissão própria:** qualquer usuário central logado vê o Financeiro.
9. **Sem fechamento mensal e sem exportação** (CSV/XLSX/PDF) para a contabilidade.

### 6.3 Conclusão

A base de cotas e vagas é sólida e auditada. O que falta é um **registro financeiro próprio**: competência, faturas, recebimentos, comissões e custos, em tabelas dedicadas em vez da auditoria. A tela atual precisa passar a ler desse registro. Antes de qualquer trabalho financeiro, é preciso fechar a autenticação e a autorização (§2), porque o Financeiro vai expor valores por parceiro.

---

## Anexo A: resultado da suíte de testes

Execução completa em 13/10/2026 com `database/schema/mysql-schema.sql` (F0, tarefa 0.7): **126 passaram e 7 falharam, em 7,5 min.** Antes do dump, a suíte não terminava.

| Teste | Causa | Relação com a F0 |
|-------|-------|------------------|
| `Auth\RegistrationTest > new users can register` | Usuário não fica autenticado após o cadastro | Já falhava antes |
| `CreateQuestionTest > cria uma question` | O teste espera redirecionar para `/dashboard`; o app vai para `/registros` | Já falhava antes (teste desatualizado) |
| `ExampleTest > returns a successful response` | `GET /` retorna 404 no ambiente de teste | Já falhava antes |
| `RelatorioBeneficiariosPdfTest > descreve…` | O teste chama `descreverFiltros()` com a assinatura antiga (sem `$tenantId`) | Já falhava antes (teste desatualizado) |
| `ImportPatientsServiceTest` (3) | O primeiro dump, feito do banco de dev, trouxe a tabela `patient_answers`, que nenhuma migration central cria | **Corrigido:** dump refeito a partir de um banco recém-migrado; os 3 passam |

Depois da correção do dump: **4 falhas, todas anteriores à F0.**

> Nota sobre o `browser.log` (§2.3): ele é escrito pelo Laravel Boost, que só liga com `APP_ENV=local` ou `APP_DEBUG=true`, e o arquivo é ignorado pelo git. Em produção, **`APP_DEBUG` precisa ser `false`**, senão o Boost (e esse log) também liga lá.
