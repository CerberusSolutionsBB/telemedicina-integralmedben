# Implementação: segurança e módulo Financeiro

> Desenho técnico das melhorias propostas em [analise.md](analise.md). A ordem de execução e os critérios de aceite estão em [desenvolvimento.md](desenvolvimento.md).
>
> Princípio: **o financeiro lê de tabelas próprias, nunca da auditoria.** A auditoria continua existindo como trilha de quem fez o quê.

---

## Parte A: correções do sistema (pré-requisito)

### A1. Autenticação nas rotas centrais

- Em `RouteModuleServiceProvider`, os módulos `pagina`, `configuracao` e `lei` passam a usar `['web', 'auth', PreventAccessFromTenantDomains::class]`.
- `form` mantém só `web`, porque tem rotas públicas (`f/{slug}`). As rotas autenticadas já ficam dentro de um grupo `auth`. É preciso conferir que `sms-templates.*` está dentro desse grupo (está).
- Em `routes/web.php`, mover `tenants.store`, `tenants.update` e `tenants.destroy` para dentro do grupo `auth`, ou remover essas rotas se forem duplicatas das de `admin.php` (que já exigem `can:admin`).
- Remover os `->middleware('auth')` avulsos de `pagina.php`, que ficam redundantes.
- Remover a rota `/teste` de `routes/tenant.php`.

### A2. Autorização por permissão

Permissões novas no `AclPermissionSeeder`, seguindo o padrão `modulo.acao` já usado em `forms.*`:

| Permissão | Rotas |
|-----------|-------|
| `paginas.view` | `pagina.index`, `pagina.show` |
| `paginas.manage` | create/store/update/status/cor/logo/planos/beneficiario/cartão/configuração |
| `paginas.delete` | `pagina.destroy`, `tenants.destroy` |
| `paginas.users` | `pagina.users.*` |
| `integracoes.manage` | `configuracoes.credencias_cluble.*` |
| `sms-templates.manage` | `sms-templates.*`, `forms.sms-templates.*` |
| `financeiro.*` | ver a Parte B |

- `TenantStoreRequest::authorize()` passa a verificar `$this->user()?->can('paginas.manage')`.
- Atribuir as permissões novas ao perfil admin no seeder, para não bloquear quem já usa o sistema.

### A3. Segredos e logs

- Remover `DOCS/dados.md` do repositório (`git rm --cached`), adicionar ao `.gitignore` e trocar a senha exposta.
- No envio de logs do navegador, mascarar props de campos `type="password"` antes de enviar, ou não enviar o `modelValue`.
- Exceções: trocar `'message' => ...$e->getMessage()` por uma mensagem genérica e manter o detalhe só no `Log::error`.

### A4. Testes de autorização

- Criar um teste que percorre `Route::getRoutes()` e falha se alguma rota central, fora de uma lista explícita de rotas públicas, não tiver `auth`. Isso impede a regressão do problema 2.1 da análise.
- Criar testes por permissão nas rotas críticas (`pagina.destroy`, `financeiro.*`).

---

## Parte B: módulo Financeiro

### B1. Conceitos

| Termo | Definição |
|-------|-----------|
| **Competência** | Mês de referência (`YYYY-MM`), no fuso `America/Sao_Paulo` |
| **Vida faturável** | Vaga em uso no plano do parceiro no fechamento da competência (`quantidade − saldo`, igual a `TenantPlanoCotaService::uso()`) |
| **Fatura** | O que a MENDBEM cobra do parceiro na competência: soma, por plano, de vidas × valor unitário (snapshot) |
| **Recebimento** | Pagamento, total ou parcial, de uma fatura |
| **Comissão** | Valor devido ao vendedor por uma venda (`registro_plano`), com status próprio |
| **Custo** | O que a MENDBEM paga pela telemedicina, por vida e por plano, com vigência |

### B2. Modelo de dados (banco central, conexão `mysql`)

Todos os valores em `decimal(12,2)`. Models com `AuditableTrait`, como `TenantPlano`.

**`financeiro_faturas`**

| Coluna | Tipo | Observação |
|--------|------|------------|
| id | bigint | |
| tenant_id | string FK `tenants.id` | |
| competencia | char(7) | `2026-10` |
| vencimento | date | |
| valor_total | decimal(12,2) | soma dos itens |
| valor_pago | decimal(12,2) default 0 | soma dos recebimentos |
| status | string | `aberta`, `paga`, `parcial`, `vencida`, `cancelada` |
| fechada_em | timestamp null | depois de fechada, os itens não mudam |
| observacao | text null | |
| timestamps | | |
| **unique** | (`tenant_id`, `competencia`) | garante que a geração é idempotente |

**`financeiro_fatura_itens`**: `fatura_id`, `cod_plano`, `vidas` (int), `valor_unitario`, `subtotal`. Guarda o snapshot do preço, o que resolve a lacuna 5 da análise.

**`financeiro_recebimentos`**: `fatura_id`, `valor`, `pago_em` (date), `forma` (`pix`, `boleto`, `transferencia`, `outro`), `user_id`, `observacao`.

**`financeiro_comissoes`**

| Coluna | Observação |
|--------|------------|
| tenant_id, cod_plano | |
| vendedor_id, vendedor_nome | nome gravado na venda (o user_id sozinho não identifica a pessoa entre central e tenant, como já é feito na auditoria) |
| paciente_id, paciente_nome | |
| origem_type, origem_id | vínculo (`TelemedicinaTenant` ou `TenantPlanoBeneficiario`) |
| valor_plano, valor_comissao | snapshot |
| competencia | mês da venda |
| status | `prevista`, `aprovada`, `paga`, `estornada` |
| pago_em, user_id (quem pagou) | |
| **unique** | (`origem_type`, `origem_id`, `cod_plano`) |

**`financeiro_custos`**: `cod_plano`, `custo_vida` (decimal), `vigencia_inicio` (date), `vigencia_fim` (date null). O custo vigente numa competência é o registro cuja vigência cobre o mês.

### B3. Serviços (`app/Services/Financeiro/`)

| Classe | Responsabilidade |
|--------|------------------|
| `FinanceiroResumoService` | KPIs e séries da tela (substitui o uso de `DashboardService` no `FinancieroController`) |
| `FaturamentoService` | `gerar(string $competencia, ?string $tenantId = null)`: idempotente, cria ou atualiza faturas **abertas** e nunca altera fatura fechada; `fechar(Fatura)`; `cancelar(Fatura)` |
| `RecebimentoService` | `registrar(Fatura, array $dados)`: grava o recebimento em transação, recalcula `valor_pago` e o `status` |
| `ComissaoService` | `registrarVenda(...)`, chamado no mesmo ponto em que `PacientePlanoService` grava a auditoria `registro_plano`; `estornar(origem)` quando a vaga é devolvida; `aprovar(ids)`; `pagar(ids, data)` |
| `CustoService` | `custoVigente(codPlano, competencia)` |

- Extrair `comissaoDoRegistro()` (hoje duplicado) para `ComissaoService`. `DesempenhoService` e `TenantDashboardService` passam a ler de `financeiro_comissoes`.
- **Backfill:** o comando `financeiro:backfill-comissoes` cria `financeiro_comissoes` a partir das auditorias `registro_plano` existentes, usando o snapshot de `comissao` quando houver e, se não houver, `TenantPlano::comissaoVenda()`. Tem que ser idempotente, garantido pelo índice único.

### B4. Fórmulas dos indicadores

| KPI | Fórmula | Fonte |
|-----|---------|-------|
| **Receita faturada** | Σ `valor_total` das faturas da competência (não canceladas) | `financeiro_faturas` |
| **MRR** | receita faturada da última competência fechada; para o mês corrente, a previsão é vidas em uso × valor atual (a regra de hoje, com o rótulo "previsto") | faturas / `tenant_planos` |
| **Recebido** | Σ `recebimentos.valor` com `pago_em` no mês | `financeiro_recebimentos` |
| **Inadimplência** | Σ (`valor_total − valor_pago`) das faturas vencidas ÷ Σ `valor_total` das faturas vencidas | faturas |
| **Custo telemedicina** | Σ por plano de vidas × `custo_vida` vigente | itens + `financeiro_custos` |
| **Margem por vida** | (receita − custo) ÷ vidas faturadas | idem |
| **Churn mensal** | vidas que saíram no mês (movimentos `devolucao`) ÷ vidas em uso no início do mês | `tenants_quantidade_parceiros` |
| **A pagar (comissões)** | Σ `valor_comissao` com status `prevista` ou `aprovada` | `financeiro_comissoes` |
| **Sinistralidade** | custo de consultas ÷ receita: **depende de dados de uso da telemedicina, que hoje não existem** (ver B8) | — |

### B5. Rotas e permissões

Módulo central `financeiro` (prefixo `financeiro`, nome `financeiro.`, middleware `web, auth, PreventAccessFromTenantDomains`). A rota antiga `/financiero` redireciona para `/financeiro`, para preservar links já salvos.

| Método | URI | Ação | Permissão |
|--------|-----|------|-----------|
| GET | `/financeiro` | Visão geral | `financeiro.view` |
| GET | `/financeiro/faturas` | Lista com filtros (competência, parceiro, status) | `financeiro.view` |
| POST | `/financeiro/faturas/gerar` | Gerar ou atualizar as faturas da competência | `financeiro.faturas` |
| GET | `/financeiro/faturas/{fatura}` | Detalhe, itens e recebimentos | `financeiro.view` |
| PUT | `/financeiro/faturas/{fatura}/fechar` | Fechar | `financeiro.faturas` |
| PUT | `/financeiro/faturas/{fatura}/cancelar` | Cancelar | `financeiro.faturas` |
| POST | `/financeiro/faturas/{fatura}/recebimentos` | Registrar pagamento | `financeiro.recebimentos` |
| GET | `/financeiro/comissoes` | Lista por status, vendedor e parceiro | `financeiro.view` |
| PUT | `/financeiro/comissoes/aprovar` | Aprovar em lote | `financeiro.comissoes` |
| PUT | `/financeiro/comissoes/pagar` | Marcar como pagas em lote | `financeiro.comissoes` |
| GET/POST/PUT | `/financeiro/custos` | Tabela de custos por plano | `financeiro.custos` |
| GET | `/financeiro/exportar/{tipo}` | CSV/XLSX (`phpoffice/phpspreadsheet`, já instalado) | `financeiro.view` |

Validação com Form Requests (`authorize()` verificando a permissão). Controllers finos, um por ação, como em `Pagina/*`.

### B6. Agendamento

- `financeiro:gerar-faturas {competencia?}`: sem argumento, gera as faturas do mês anterior. Agendar em `routes/console.php` para o dia 1, às 03:00 (`America/Sao_Paulo`).
- `financeiro:marcar-vencidas`: diário; passa para `vencida` as faturas abertas ou parciais com vencimento no passado.

### B7. Front-end

O padrão segue o da casa (ver o Controle de Acesso): página fina, componentes e composables por assunto, layout simples com 100% de largura.

- `Pages/Financeiro/Index.vue`: abas **Visão geral**, **Faturas**, **Comissões**, **Custos**, com a aba ativa persistida em `useAbaAtiva`, como em `Pagina/Show.vue`.
- A visão geral reaproveita o visual atual dos cards e a tabela "Desempenho por parceiro", trocando os `—` por valores reais e mostrando "Sem dados" quando a fonte estiver vazia.
- Composables: `useFinanceiroFiltros`, `useFaturas`, `useComissoes`, `useCustos`. Requisições com `router`/`useForm` do Inertia, sem `fetch` manual.
- Valores formatados em BRL com `toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })`. Datas e competências exibidas como `out/2026`.
- Renomear `Financiero` para `Financeiro` (pasta, controller e rota).

### B8. Pendências que dependem de decisão do negócio

1. **Base da fatura:** cobrar pelas **vidas em uso** no fechamento, pela **quantidade contratada** (vagas reservadas) ou um valor fixo por contrato?
2. **Vencimento:** dia fixo, por exemplo dia 10? Igual para todos os parceiros ou configurável por parceiro?
3. **Pró-rata:** um beneficiário que entra no meio do mês paga o mês cheio?
4. **Comissão:** quem é o beneficiário da comissão, o usuário do tenant (vendedor do parceiro) ou um representante da MENDBEM? Quando ela vence: na venda ou no pagamento da fatura?
5. **Custos de telemedicina:** existe um custo fixo por vida por plano? Os dados de consultas (para sinistralidade) virão de alguma API do fornecedor?
6. **Emissão de cobrança:** apenas controle interno, ou integração futura com um gateway (boleto/PIX)? O modelo acima já deixa espaço (`forma`, recebimentos), mas não inclui gateway.
