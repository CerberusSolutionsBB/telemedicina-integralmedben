# Módulo Processos (painel do parceiro)

> Status: **fatias 1, 2 e 4 entregues** (branch `feature/processos`). Fatias 3 e 5 pendentes.
> Data: 2026-10-23

## 1. Pedido original (interpretado)

| Pedido | Interpretação |
| --- | --- |
| `processos`: id, título, descrição, ícone, status (padrão `true`), `campo_status` (padrão `false`), valor | Cadastro com ícone igual a Serviço/Convênio, com mais dois campos: `campo_status` (se o processo **usa campos personalizados**) e `valor` (preço base, R$) |
| `processos_campos`: id, campo, valor do campo, tipo do campo, valor do processo, data e horário | Campos personalizados de um processo. Só valem quando `campo_status = true`. **Falta `processo_id`** no pedido; ele é obrigatório |
| `processos_servicos`: associar serviço a um processo | Tabela pivô N:N entre `processos` e `servicos` |
| "Na página de parceiro" | Segue o padrão atual: o admin **liga o módulo** na aba Configuração da página do parceiro (`/pagina/{tenant}`), e o CRUD fica no **painel do parceiro** (banco do tenant) |

## 2. Encaixe na arquitetura atual

O módulo segue o mesmo caminho de **Serviços / Convênios / Cupons de benefício** (PR #115):

- **Banco do tenant**: migrations em `database/migrations/tenant/`.
- **Módulo opcional**: chave `processos` em `App\Support\ModulosParceiro::MODULOS`, ligada por parceiro (`tenants_details.configuracao['modulos']`) e protegida pelo middleware `modulo:processos`. `PaginaModuloController` e `EbaModulos.vue` já leem `MODULOS`, então o toggle aparece sozinho.
- **CRUD genérico**: `ProcessoController extends CadastroIconeController`, como faz o `CupomBeneficioController` (sobrescreve `paraTela`, `opcoesExtras`, `consultaLista`, `camposHistorico`, `valorHistorico`).
- **Validação**: `ProcessoRequest extends CadastroIconeRequest`.
- **Auditoria**: `Processo` implementa `Auditable` + `AuditoriaDoTenant` (tag `tenant:{id}` no banco central). O histórico do Show vem pelo `CadastroIconeService::historico()`.
- **Front**: telas `Tenant/Processo/*` usando `CadastroIconeEdicao`/`Detalhe`/`Lista` + slot `campos`; módulo `PROCESSOS` em `Composables/CadastroIcone/modulos.js`; item no `MODULOS_MENU` do `TenantAdminLayout.vue`.

### Ponto que exige mudança no genérico

`CadastroIconeController::store/update` fazem só `Model::create($validados)` / `update()`. O processo grava **três tabelas** (processo + campos + serviços), então precisa de um gancho:

```php
// CadastroIconeController (novo, no-op por padrão)
protected function salvarRelacoes(Model $item, array $dados): void {}
```

`store`/`update` passam a rodar dentro de `DB::transaction()` e chamar `salvarRelacoes()`. Os outros módulos não mudam de comportamento. Os arrays `campos` e `servicos` não estão no `$fillable`, então o Eloquent os ignora no `create`.

## 3. Modelo de dados (banco do tenant)

### 3.1 `processos`

| Coluna | Tipo | Regra |
| --- | --- | --- |
| id | bigint PK | |
| titulo | string(120) | obrigatório |
| descricao | text null | até 1000 |
| icone | string(50) | componente lucide (`IconesLucide::REGRA`) |
| status | boolean, default `true` | index |
| campo_status | boolean, default `false` | liga a seção de campos personalizados |
| valor | decimal(10,2), default `0` | R$, ≥ 0 |
| timestamps | | |

### 3.2 `processos_campos`

| Coluna | Tipo | Regra |
| --- | --- | --- |
| id | bigint PK | |
| processo_id | FK → processos, `cascadeOnDelete` | **não estava no pedido; é necessário** |
| campo | string(120) | rótulo do campo (ex.: "Nº do protocolo") |
| tipo_campo | string(20) | `texto`, `numero`, `moeda`, `data`, `data_hora`, `sim_nao` (constante `ProcessoCampo::TIPOS`) |
| valor_campo | text null | valor ou valor padrão, validado conforme `tipo_campo` |
| valor_processo | decimal(10,2) null | valor (R$) que o campo soma ao processo (ver dúvida 3) |
| data_hora | datetime null | "data e horário" do pedido (ver dúvida 2) |
| ordem | unsigned smallint, default 0 | ordem de exibição no formulário |
| timestamps | | |

Índice: `(processo_id, ordem)`.

### 3.3 `processos_servicos` (pivô)

| Coluna | Tipo | Regra |
| --- | --- | --- |
| id | bigint PK | |
| processo_id | FK → processos, `cascadeOnDelete` | |
| servico_id | FK → servicos, `restrictOnDelete` | igual ao cupom: serviço em uso não é excluído, o `destroy` genérico já mostra "está em uso… Inative-o." |
| timestamps | | |

Único: `(processo_id, servico_id)`.

> Os nomes `processos_campos` e `processos_servicos` são os do pedido. Pela convenção do Laravel seriam `processo_campos` e `processo_servico`, então os models declaram `$table` e o `belongsToMany` recebe o nome da tabela explicitamente.

### 3.4 Models

```
Processo (Auditable, AuditoriaDoTenant)
  hasMany    campos()   -> ProcessoCampo (orderBy ordem)
  belongsToMany servicos() -> Servico, 'processos_servicos'
ProcessoCampo (Auditable, AuditoriaDoTenant)
  belongsTo  processo()
Servico
  belongsToMany processos()  (opcional, para mostrar "usado em" no Show do serviço)
```

## 4. Regras de negócio

1. `campo_status = false`: a seção "Campos" fica escondida e o backend **ignora** `campos[]` (os campos já salvos continuam no banco, ver dúvida 4).
2. `campo_status = true`: exige ao menos 1 campo (`campos` com `required_if:campo_status,true|array|min:1`).
3. Cada campo: `campo` obrigatório (até 120); `tipo_campo` em `ProcessoCampo::TIPOS`; `valor_campo` validado pelo tipo (`numero`/`moeda` → numeric, `data` → date, `data_hora` → date_format, `sim_nao` → boolean, `texto` → até 1000).
4. Salvar campos é sincronizar: o front manda a lista com `id` nos existentes; o backend atualiza esses, cria os sem `id` e apaga os que sumiram (`whereNotIn`). `ordem` = posição no array.
5. Serviços: `servicos` é `array` de ids com `Rule::exists('servicos','id')`. Gravação via `$processo->auditSync('servicos', $ids)` (laravel-auditing v14), que registra a mudança do vínculo na auditoria.
6. Serviços **inativos** aparecem nas opções, como no cupom, para que um processo já vinculado continue editável.
7. A seção "Serviços" fica **no lugar da seção Status** do formulário (slot `status` do `CadastroIconeForm`). O processo nasce ativo; ativar/inativar fica no detalhe e na listagem. Serviços são opcionais, escolhidos num modal de múltipla escolha (`SeletorMultiploModal` com `permite-vazio`).
8. **Cupom no processo:** na aba *Processos* da tela Relatório (grade), cada processo tem o campo "Código do cupom" (`POST processos/{id}/cupom`, `ProcessoCupomService`). Vale cupom de benefício ativo, no prazo e cujo `servico_id` esteja entre os serviços do processo; percentual sobre o `valor` ou valor fixo (sem ficar negativo). Cupom de plano específico só avisa o plano. O campo só aparece com o módulo Cupons de benefício ligado. "Aplicar" só mostra a prévia; **"Registrar uso"** grava em `processos_cupons_usos` (processo, cupom, usuário logado, código, desconto, valores, data) via `POST processos/{id}/cupom/usar`, validando de novo na hora. Nome do usuário e código ficam guardados no registro (cupom/usuário excluídos não apagam o histórico). O detalhe do processo mostra a seção **Usos de cupom** (quem, quando, cupom, valores).
9. Excluir processo: apaga campos e vínculos em cascata e fica no histórico pelo audit `deleted`.

## 5. Backend: arquivos

| Arquivo | O que faz |
| --- | --- |
| `database/migrations/tenant/2026_10_23_xxxxxx_create_processos_table.php` | tabela `processos` |
| `database/migrations/tenant/2026_10_23_xxxxxx_create_processos_campos_table.php` | tabela `processos_campos` |
| `database/migrations/tenant/2026_10_23_xxxxxx_create_processos_servicos_table.php` | pivô |
| `app/Models/Processo.php`, `app/Models/ProcessoCampo.php` | models + `TIPOS`, `valorFormatado()` |
| `app/Http/Requests/ProcessoRequest.php` | estende `CadastroIconeRequest` (`campo_status`, `valor`, `campos.*`, `servicos.*`) |
| `app/Http/Controllers/Tenant/ProcessoController.php` | estende `CadastroIconeController`; `salvarRelacoes()`, `paraTela()` (campos + serviços), `opcoesExtras()` (`servicos`, `tiposCampo`), `consultaLista()` com `withCount('campos')` + `with('servicos:id,titulo,icone')` |
| `app/Http/Controllers/Tenant/CadastroIconeController.php` | gancho `salvarRelacoes()` + transação |
| `app/Support/ModulosParceiro.php` | `'processos' => ['label' => 'Processos', 'descricao' => '…']` |
| `routes/tenant.php` | grupo `modulo:processos` com `resource` + `PUT processos/{id}/status` |

## 6. Front: arquivos

Seguir a memória do projeto: **página fina + formulário + composables, breadcrumb como no ACL, 100% largura, sem extras de UX.**

| Arquivo | O que faz |
| --- | --- |
| `resources/js/Composables/CadastroIcone/modulos.js` | `PROCESSOS` (`rota: 'processos'`, textos, `secoesExtras: 3`, `valoresIniciais`: `valor`, `campo_status`, `campos: []`, `servicos: []`) |
| `resources/js/Pages/Tenant/Processo/{Index,Create,Edit,Show}.vue` | iguais às de `Cupom/*`, passando o slot `campos` |
| `resources/js/Components/Processo/ProcessoCampos.vue` | seções: **Valor** (moeda), **Campos personalizados** (switch `campo_status` + lista repetível: rótulo, tipo, valor, valor do processo, data/hora; adicionar/remover/subir/descer), **Serviços** (checkboxes com ícone; inativos marcados como "inativo") |
| `resources/js/Composables/Processo/useCamposProcesso.js` | adicionar/remover/reordenar campos, input conforme o tipo |
| `resources/js/Layouts/TenantAdminLayout.vue` | `{ modulo: 'processos', grupo: 'Administração', label: 'Processos', routeName: 'processos.index', match: 'processos.*', icon: Workflow }` |

## 7. Testes

`tests/Feature/ProcessoTest.php`, no mesmo esquema do `CadastroIconeTest` (adicionar as 3 migrations ao `--path` do `migrateFreshUsing`):

- validação: `campo_status=true` sem campos falha; tipo inválido falha; `valor_campo` incompatível com o tipo falha; serviço inexistente falha;
- store/update grava processo + campos (com ordem) + vínculos; o update apaga os campos removidos;
- `campo_status=false` ignora `campos[]`;
- serviço vinculado a processo não pode ser excluído (mensagem "em uso");
- módulo desligado → redirect para `tenant.dashboard` com erro;
- auditoria: create/update de processo com a tag `tenant:{id}`; `auditSync` dos serviços.

Lembrete: a suíte usa `mysql-schema.sql`. Se ele for regenerado, usar um banco recém-migrado (ver memória *Testes e schema dump*).

## 8. Orquestração (fatias entregáveis)

Cada fatia fecha com testes verdes e pode virar commit próprio.

| # | Fatia | Entrega | Depende de |
| --- | --- | --- | --- |
| 1 ✅ | **Base do processo** | migration `processos`, model, `ProcessoRequest` (sem campos/serviços), controller, rotas, módulo em `ModulosParceiro`, menu, telas Index/Create/Edit/Show com a seção Valor + switch `campo_status` | — |
| 2 ✅ | **Gancho genérico** | `salvarRelacoes()` + transação no `CadastroIconeController`; testes dos módulos atuais seguem verdes | 1 |
| 3 | **Campos personalizados** | migration `processos_campos`, `ProcessoCampo`, validação `campos.*`, sync no `salvarRelacoes`, componente repetível, exibição no Show | 2 |
| 4 ✅ | **Vínculo com serviços** | migration pivô, `belongsToMany`, `auditSync`, checkboxes no form, lista no Show, regra "serviço em uso" | 2 |
| 5 | **Histórico e acabamento** | `camposHistorico`/`valorHistorico` (valor, campo_status), contagem de campos e serviços na listagem, `pint` nos arquivos novos | 3, 4 |

As fatias 3 e 4 são independentes e podem ser feitas em paralelo.

> Fatia 1 também já trouxe o histórico de `valor` e `campo_status` (era da fatia 5). As rotas vêm do `@routes` (Blade); o `resources/js/ziggy.js` não é usado e não precisa ser regenerado.

## 9. Dúvidas em aberto (decidir antes da fatia 3)

1. **O que é um "campo"?** (a) **definição** do campo (o parceiro cria "Nº do protocolo, tipo texto", e o valor é preenchido depois, por beneficiário ou atendimento) ou (b) um **registro já preenchido** (campo + valor + data/hora, como uma etapa do processo). A proposta acima atende (b) e serve de base para (a). Se for (a), falta a tabela de respostas (`processos_campos_valores` ligada a beneficiário).
2. **"Data e horário"** é um dado do campo (ex.: data do agendamento) ou só o momento do registro? No segundo caso já temos `created_at` e a coluna `data_hora` sai.
3. **"Valor do processo" no campo**: é um valor que o campo **soma** ao processo (total = `valor` + Σ `valor_processo`) ou é outra coisa? Define se a listagem mostra o total.
4. Ao **desligar** `campo_status`, os campos salvos devem ser **apagados** ou só **escondidos** (proposta: escondidos)?
5. O processo tem relação com **beneficiário** (abrir um processo para um paciente) ou é só um catálogo, como Serviços? Isso muda o escopo do módulo.
6. **Tipos de campo**: a lista `texto, numero, moeda, data, data_hora, sim_nao` basta? Precisa de `selecao` (com opções)?
