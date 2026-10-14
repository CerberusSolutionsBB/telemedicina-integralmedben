# Desenvolvimento: guia e plano de execução

> Como rodar o projeto, quais convenções seguir e em que ordem entregar as melhorias de [analise.md](analise.md), seguindo o desenho de [implementacao.md](implementacao.md).

---

## 1. Ambiente

| Peça | Como rodar |
|------|------------|
| App | `php artisan serve` → http://localhost:8000 |
| Front (HMR) | `npm run dev` (Vite em `127.0.0.1:5173`) |
| Banco | MariaDB local: `.env` com `DB_HOST=127.0.0.1`, `DB_USERNAME=root`, `DB_DATABASE=telemedicinamedben`; tenants em bancos próprios |
| Fila | `QUEUE_CONNECTION=database` → `php artisan queue:work` quando precisar de jobs (SMS, integrações) |
| Docker (opcional) | `make up` sobe nginx, php-fpm, mysql e redis via Podman. **Exige `DB_HOST=mysql` no `.env`**, então não dá para misturar com o `artisan serve` |

Erros comuns:

- `getaddrinfo for mysql failed` → o `.env` está apontando para o container. Volte para `127.0.0.1`.
- `Connection refused 127.0.0.1:3306` dentro do container → o inverso.
- Mudança no front que não aparece → rode `npm run dev` ou `npm run build`.

---

## 2. Convenções

**Backend**

- Controllers finos e invocáveis por ação (padrão `Pagina/*`); a regra de negócio fica em services. Código novo vai em `app/Services/<Domínio>/`; `app/Http/Services` é legado e não deve receber código novo.
- Validação e autorização em Form Request (`authorize()` **não** pode retornar `true` em rotas administrativas).
- Toda rota nova declara `auth` e `permission:` (ou fica num módulo que já declare).
- Dinheiro em `decimal(12,2)` com cast `decimal:2`; os cálculos arredondam com `round(..., 2)` no fim, nunca no meio.
- Datas gravadas em UTC e filtradas no horário de Brasília (`Formatar::localParaUtc`), como em `PlanoHistoricoService`.
- Operações de saldo e financeiras rodam em `DB::connection('mysql')->transaction()` com `lockForUpdate()` (padrão `TenantPlanoCotaService`).
- Mensagem de erro ao usuário é genérica; o detalhe vai para `Log::error` com contexto.
- `vendor/bin/pint --dirty` antes de cada commit.

**Front-end**

- Página fina em `Pages/`, componentes em `Components/<Domínio>/`, lógica em `Composables/<Domínio>/useX.js`.
- Formulários seguem o padrão Create/Edit da casa: breadcrumb como no Controle de Acesso, cards com 100% de largura, Cancelar/Salvar no fim, sem extras de UX não pedidos.
- Requisições com `router`/`useForm` do Inertia; nada de `fetch` com XSRF montado à mão em código novo.
- Cores do projeto (ciano `#22b8cf` / `#23BACF`) e componentes de `Components/ui`.

**Git**

- Uma branch por fase (`feature/financeiro-f0`, `feature/financeiro-f1`...), com commits pequenos no padrão `tipo(escopo): descrição` (`feat(financeiro): ...`, `fix(seguranca): ...`).
- Cada PR atualiza o `CHANGELOG.md`.

---

## 3. Plano de execução

As fases são incrementais e cada uma é entregável sozinha. **F0 é obrigatória antes das demais.**

### F0: Segurança e base (branch atual `feature/financeiro-f0`)

> **Status em 13/10/2026:** 0.1, 0.2, 0.3, 0.4, 0.6, 0.7 e 0.8 (parcial) **feitas**. 0.5 não exige código (ver o anexo A da análise). Pendente: trocar a senha exposta em `DOCS/dados.md` e limpar o histórico do git, se for o caso.
>
> - Rotas: `pagina`, `configuracao` e `lei` exigem `auth`; cada rota tem `permission:` (`paginas.*`, `leis.*`, `forms.edit`, `integracoes.manage`, `sms-templates.manage`, `users.*`, `financeiro.view`). Logo e imagens do cartão continuam públicos.
> - Permissões novas por migration (`2026_10_14_013424_...`): `financeiro.view` e `integracoes.manage` → Admin; `sms-templates.manage` → Admin e Manager. As existentes não foram alteradas.
> - Usuário **sem perfil** perde o acesso às telas administrativas. Antes de publicar, conferir os perfis em produção.
> - `/financiero` → `/financeiro` (redirect 301); a tela recebe só `pages`, `planosParceiros` e `updatedAt`. O `FinanceiroResumoService` próprio fica para a F2, junto com as faturas.
> - Testes novos: `RotasCentraisProtegidasTest` (varredura de rotas, sem banco) e `AcessoPorPermissaoTest`.

| # | Tarefa | Aceite | Testes |
|---|--------|--------|--------|
| 0.1 | `auth` nos módulos `pagina`, `configuracao` e `lei`; mover ou remover `tenants.store/update/destroy`; remover `/teste` | `GET /pagina/{tenant}` anônimo → redireciona para o login | Teste que varre as rotas e falha se uma rota central fora da lista de públicas não tiver `auth` |
| 0.2 | Permissões `paginas.*`, `integracoes.manage`, `sms-templates.manage`, `financeiro.view` no seeder e nas rotas | Usuário sem permissão → 403; admin continua com acesso total | Feature tests: 403 sem permissão, 200 com permissão em `pagina.destroy` e `financeiro.index` |
| 0.3 | `TenantStoreRequest::authorize()` com permissão | — | Coberto em 0.2 |
| 0.4 | Tirar `DOCS/dados.md` do git e trocar a senha | Arquivo ausente em `git ls-files` | — |
| 0.5 | Mascarar senha nos logs do navegador | `browser.log` não contém o valor de campo password | — |
| 0.6 | Trocar `$e->getMessage()` nas respostas por mensagem genérica | Nenhum `getMessage()` em resposta JSON ou flash | — |
| 0.7 | Acelerar a suíte de testes (ver §4) | Suíte completa em menos de 2 min | — |
| 0.8 | `FinanceiroResumoService` + rota `/financeiro` (redirect de `/financiero`), sem depender de `DashboardService` | Tela atual igual, com payload só do que usa | Teste do service: MRR previsto = Σ vidas em uso × valor |

### F1: Comissões

| # | Tarefa | Aceite | Testes |
|---|--------|--------|--------|
| 1.1 | Migration e model `financeiro_comissoes` | — | — |
| 1.2 | `ComissaoService::registrarVenda` chamado onde a auditoria `registro_plano` é gravada; `estornar` na devolução de vaga | Cada venda nova gera uma comissão `prevista`; devolver a vaga estorna | Venda gera uma comissão com o snapshot correto (percentual e fixo); a mesma venda não duplica; devolução estorna |
| 1.3 | Comando `financeiro:backfill-comissoes` | Rodar duas vezes gera o mesmo resultado | Teste do comando (idempotência) |
| 1.4 | `DesempenhoService` e `TenantDashboardService` passam a ler de `financeiro_comissoes`; remover o `comissaoDoRegistro()` duplicado | Os números do dashboard do tenant e do desempenho não mudam | `DesempenhoServiceTest` e `TenantDashboardServiceTest` continuam passando |
| 1.5 | Aba **Comissões** (lista, filtros, aprovar e pagar em lote) + card "A pagar" | O card mostra o total `prevista + aprovada` | Feature: aprovar e pagar exigem `financeiro.comissoes` |

### F2: Faturamento e recebimentos

**Depende das decisões B8.1 a B8.3** de [implementacao.md](implementacao.md#b8-pendências-que-dependem-de-decisão-do-negócio).

| # | Tarefa | Aceite | Testes |
|---|--------|--------|--------|
| 2.1 | Migrations e models de faturas, itens e recebimentos | — | — |
| 2.2 | `FaturamentoService::gerar` (idempotente, não toca em fatura fechada) + comando agendado | Regerar a competência atualiza só as abertas | Geração correta por plano; regerar não duplica; fatura fechada fica intacta |
| 2.3 | `RecebimentoService` (parcial, total, status) + `financeiro:marcar-vencidas` | Status coerente com os pagamentos | Pagamento parcial → `parcial`; total → `paga`; vencida sem pagamento → `vencida` |
| 2.4 | Aba **Faturas** (lista, detalhe, fechar, cancelar, registrar pagamento) | — | Feature: permissões `financeiro.faturas` e `financeiro.recebimentos` |
| 2.5 | KPIs Receita faturada, Recebido, Inadimplência e MRR real | Os cards deixam de mostrar `—` | Testes das fórmulas no `FinanceiroResumoService` |

### F3: Custos e margem

**Depende da decisão B8.5.**

| # | Tarefa | Aceite | Testes |
|---|--------|--------|--------|
| 3.1 | `financeiro_custos` + `CustoService::custoVigente` + aba **Custos** | Custo com vigência e sem sobreposição | Vigência correta na virada do mês; sobreposição é rejeitada |
| 3.2 | KPIs Custo telemedicina, Margem por vida e Churn mensal | — | Testes das fórmulas |

### F4: Relatórios e escala

| # | Tarefa | Aceite |
|---|--------|--------|
| 4.1 | Exportação CSV/XLSX de faturas, comissões e resumo | Arquivo abre no Excel com valores em BRL |
| 4.2 | Snapshot mensal do resumo (ou cache por competência) para não percorrer todos os tenants a cada acesso | Visão geral em menos de 500 ms com 100 tenants |
| 4.3 | Sinistralidade, se houver fonte de dados de consultas | — |

### Melhorias gerais (em paralelo, sem bloquear o financeiro)

- Quebrar `Pagina/Show.vue` em componentes por aba, como já foi feito com `Ebas/*`.
- Quebrar `PublicFormController` em services (envio de SMS, montagem de dados, gravação).
- Unificar a forma de obter o tenant atual (`tenant()`), eliminando `str($host)->before('.')`.
- Migrar o histórico de planos da auditoria para uma tabela própria e filtrar no SQL.
- Remover o código comentado (verificação de telefone por SMS, `enviarSmsTemplate` antigo).
- Retomar o `CHANGELOG.md`.

---

## 4. Testes

**Problema:** cada processo de teste roda todas as migrations no MariaDB local, e cada DDL leva segundos. Um arquivo de teste passa de 90 segundos.

**Opções, em ordem de preferência:**

1. **Schema dump (adotado):** gere o dump **a partir de um banco recém-migrado**, nunca do banco de dev (ele tem tabelas a mais). Comandos: `rm database/schema/mysql-schema.sql && DB_DATABASE=teste_testing php artisan migrate:fresh --force && DB_DATABASE=teste_testing php artisan schema:dump`. Refazer sempre que entrar uma migration nova de estrutura.
   Descrição original: `php artisan schema:dump` gera `database/schema/mysql-schema.sql`, que é carregado de uma vez antes das migrations novas. É o menor esforço.
2. **Ajuste do MariaDB de dev** (só na máquina local): `innodb_flush_log_at_trx_commit=0` e `innodb_flush_method=nosync`. Com essa configuração, o DDL no container caiu de 20-45 s para menos de 1 s.
3. **SQLite em memória:** mais rápido, mas há SQL específico de MySQL (`MONTH()`, colunas JSON, `orderByRaw("event = ...")`) e o tenancy cria bancos, então exigiria adaptações.

**Regras:**

- Rodar o teste mais específico durante o desenvolvimento: `php artisan test --compact --filter=NomeDoTeste`.
- Antes do PR, rodar a suíte completa: `php artisan test --compact`.
- Todo service financeiro novo tem um teste de fórmula com valores conhecidos (percentual, fixo, arredondamento, competência na virada do mês).
- Toda rota nova tem teste de 403 sem permissão.

---

## 5. Checklist de PR

- [ ] Rotas novas com `auth` e `permission:`; Form Request com `authorize()` real
- [ ] Valores em `decimal(12,2)`, operações em transação, nada lido da auditoria para cálculo
- [ ] Testes do comportamento e das falhas importantes passando
- [ ] `vendor/bin/pint --dirty` sem pendências
- [ ] `npm run build` sem erros
- [ ] Nenhum segredo, `.env` ou log no diff
- [ ] `CHANGELOG.md` atualizado
