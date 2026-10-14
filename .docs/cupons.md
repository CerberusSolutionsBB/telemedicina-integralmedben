# Cupons de desconto: análise e implementação

> Análise do documento [deepseek_markdown_20261014_98d4f6.md](deepseek_markdown_20261014_98d4f6.md) diante do código atual e proposta de implementação adaptada ao MENDBEM. Complementa [implementacao.md](implementacao.md) (módulo Financeiro).

---

## 0. Status da implementação (14/10/2026)

**Decisões tomadas:** cupom de **venda** · criado pelo **admin e pelo parceiro, com teto** · comissão percentual sobre o **valor com desconto** · devolução da vaga **estorna** o uso.

**Feito (C1 a C7 da §8):**

| Parte | Onde |
|-------|------|
| Tabelas `cupons`, `cupom_planos`, `cupom_usos` | `database/migrations/2026_10_14_020934_create_cupons_tables.php` |
| Models | `Cupom` (com `situacao()`, `descontoSobre()`), `CupomPlano`, `CupomUso` |
| Regras, aplicação com lock, estorno, teto | `app/Services/Cupom/CupomService.php` |
| Venda com cupom (snapshot `valor`, `valor_original`, `cupom`, `desconto`; comissão sobre o valor final) | `PacientePlanoService::registrar*` / `auditarRegistro`, `TenantPlano::comissaoVenda($valorVenda)` |
| Estorno na devolução | `TenantPlanoCotaService::devolver` |
| Tela central (Financeiro → Cupons), permissão `cupons.manage` (Admin) | `Financeiro\CupomController`, `Pages/Financeiro/Cupons/*` |
| Teto do parceiro (aba Beneficiário da página do parceiro) | `PaginaCupomController`, `Ebas/EbaCupomTeto.vue` |
| Tela do parceiro (menu "Cupons", só com teto definido) | `Tenant\CupomController`, `Pages/Tenant/Cupons/*` |
| Campo "Cupom" + prévia no cadastro manual e no formulário público | `Cupom/CampoCupom.vue`, `cupons.validar` (throttle 20/min), `forms.public.cupom` (throttle 10/min) |
| Testes | `CupomVendaTest` (6), `CupomTelasTest` (5) |

**Comportamentos a conhecer:**

- **Teto:** sem teto, o parceiro não vê o menu nem cria cupons, e os cupons que ele já criou deixam de valer. Valor fixo é aceito, mas o desconto na venda fica limitado a `teto%` do plano.
- **Painel do parceiro sem permissão própria:** qualquer usuário logado do parceiro acessa "Cupons", como nas telas Configurações e Desempenho, porque vários bancos de tenant não têm perfis cadastrados. O controle é o teto. Se precisar restringir por perfil, é preciso popular as permissões dos tenants antes.
- **Corrida:** o cupom é validado antes da SIPROV e revalidado com lock na transação do vínculo. Se ele acabar entre as duas etapas, o vínculo não é criado e o erro aparece (mesmo comportamento da falta de vaga).
- **Primeiro contrato:** como vínculos removidos são apagados, a regra é "CPF sem outro vínculo no parceiro e que nunca usou cupom nele".
- **Plano sem valor** (`tenant_planos.valor` vazio): o cupom não se aplica, porque não há o que descontar.
- **Fora do escopo:** cupom de fatura (fica para a F2, pelo campo `alvo`), `trial_estendido` e relatório/exportação (C8).

---

## 1. O que o documento traz

- **Está incompleto:** o índice lista 13 seções, mas o arquivo termina na **2.1 (tabela `cupom`)**. Máquina de estados, regras de validação, fluxos, anti-fraude, endpoints e checklist não estão no arquivo.
- O que existe é um modelo **genérico de SaaS** que não foi adaptado ao projeto:

| Item do documento | Situação no MENDBEM | Ajuste |
|-------------------|---------------------|--------|
| SQL PostgreSQL (`BIGSERIAL`, `NOW()`, `CHECK`) | MariaDB + migrations Laravel | Migration Laravel; validação de datas e valor no Form Request |
| `parceiro(id)` BIGINT | Parceiro é `tenants.id`, **string** | `tenant_id` string, FK `tenants.id` |
| `usuario(id)` | `users`; o mesmo model existe no central e em cada tenant | `user_id` + **nome gravado** (padrão já usado em `auditarRegistro`) |
| Venda com checkout e pagamento | **Não existe checkout.** A "venda" é o **vínculo do beneficiário ao plano** (`PacientePlanoService::registrarVinculo*` + auditoria `registro_plano`) | O cupom é aplicado no vínculo |
| `trial_estendido` | Não existe trial no sistema | **Fora do escopo** até existir cobrança recorrente (F2) |
| `uso_atual` como contador simples | Concorrência real (formulário público) | Incrementar com `lockForUpdate`, como em `TenantPlanoCotaService` |
| `status` gravado (`ativo`, `expirado`, `esgotado`) | — | Gravar só o status manual (`ativo`, `pausado`, `cancelado`); **expirado e esgotado são calculados**, sem depender de job |
| "por cliente" | Cliente = beneficiário, identificado pelo **CPF** | Limite por CPF normalizado |
| `aplicavel_a = planos_especificos` | Planos por código (`config/siprov.planos` + `config/planos.internos`) | Tabela de planos do cupom |
| `aplicavel_a = primeiro_contrato` | — | "CPF sem vínculo anterior em nenhum plano do tenant" (`TenantPlanoCotaService::usoPorVinculos` / `vinculoAtual`) |

---

## 2. A decisão que trava tudo: o desconto incide sobre o quê?

Hoje `tenant_planos.valor` é **um único valor por plano e parceiro**, gravado como snapshot na venda (`registro_plano.valor`) e usado no MRR e na comissão percentual. Um cupom pode significar duas coisas bem diferentes:

| | **A. Cupom de venda (parceiro → beneficiário)** | **B. Cupom de faturamento (MENDBEM → parceiro)** |
|-|-------------------------------------------------|--------------------------------------------------|
| Onde entra | Cadastro manual do beneficiário e formulário público | Fatura mensal do parceiro |
| O que muda | Valor da venda gravado → **comissão percentual** e receita do parceiro | Valor cobrado do parceiro na competência |
| Quem cria | Admin (global) e o próprio parceiro (só para ele) | Só admin |
| Dá para fazer agora? | **Sim**, o fluxo de venda existe | **Não**, depende das faturas (F2) |

**Recomendação:** implementar **A** agora, deixando `alvo` (`venda` / `fatura`) na tabela para B entrar na F2 sem nova modelagem.

---

## 3. Modelo de dados (banco central, conexão `mysql`)

**`cupons`**

| Coluna | Tipo | Observação |
|--------|------|------------|
| id | bigint | |
| codigo | string(50) **unique** | sempre MAIÚSCULO, sem espaços |
| alvo | string | `venda` (agora) / `fatura` (F2) |
| tipo | string | `percentual` / `valor_fixo` |
| valor | decimal(10,2) | > 0; percentual ≤ 100 |
| tenant_id | string null FK | null = cupom global (admin) |
| criado_por_id, criado_por_nome | | |
| origem | string | `manual`, `campanha`, `indicacao`, `suporte` |
| inicio_em, expira_em | datetime | UTC; `expira_em > inicio_em` |
| limite_uso_total | int null | null = ilimitado |
| limite_uso_por_cpf | int default 1 | |
| usos | int default 0 | atualizado com lock |
| status | string | `ativo`, `pausado`, `cancelado` |
| valor_minimo | decimal(10,2) default 0 | valor do plano mínimo |
| aplicavel_a | string | `todos`, `planos`, `primeiro_contrato` |
| acumulavel | boolean | sempre `false` por ora (um cupom por venda) |
| observacoes | text null | |
| timestamps | | índices: `status`, `tenant_id`, `expira_em` |

**`cupom_planos`**: `cupom_id`, `cod_plano` (unique no par). Usada quando `aplicavel_a = planos`.

**`cupom_usos`**: `cupom_id`, `tenant_id`, `cpf`, `paciente_nome`, `cod_plano`, `origem_type` / `origem_id` (vínculo), `valor_original`, `desconto`, `valor_final`, `user_id` / `usuario_nome`, `ip`, `estornado_em` (null), timestamps. Índice em (`cupom_id`, `cpf`).

Models com `AuditableTrait` (como `TenantPlano`). Status efetivo calculado no model:

```php
public function situacao(): string // ativo | pausado | cancelado | agendado | expirado | esgotado
```

---

## 4. Regras de validação (`CupomService::validar`)

Em ordem, cada uma com uma mensagem clara para o usuário:

1. Código existe (comparação case-insensitive, depois de `trim`).
2. `status = ativo`, `now() >= inicio_em` e `now() < expira_em`.
3. Cupom global, **ou** `tenant_id` igual ao tenant da venda. O cupom de um parceiro nunca vale em outro (mesmo cuidado dos templates SMS).
4. `limite_uso_total` não atingido (`usos` não estornados).
5. Usos do CPF abaixo de `limite_uso_por_cpf`.
6. Plano permitido (`aplicavel_a = planos` → o plano está em `cupom_planos`).
7. `primeiro_contrato` → o CPF não tem vínculo anterior no tenant.
8. `valor do plano >= valor_minimo`.
9. Desconto: percentual → `round(valor × pct / 100, 2)`; fixo → `min(valor, fixo)`. `valor_final` nunca fica negativo.

---

## 5. Fluxo de aplicação

```
Cadastro manual / formulário público (campo "Cupom", opcional)
  → CupomService::validar(...)          prévia (sem lock) para mostrar o desconto
  → PacientePlanoService::registrarVinculo*(..., ?string $cupom)
       transação mysql:
         TenantPlanoCotaService::consumir     (vaga, já existente)
         CupomService::aplicar                lockForUpdate no cupom, revalida, usos++,
                                              cria cupom_usos com o snapshot
  → auditarRegistro grava também: cupom, desconto, valor_final
     e a comissão percentual é calculada sobre valor_final
```

- **Pontos de entrada:** `PatientController@store` (origem `cadastro_paciente`) e `PublicFormController` (`formulario_publico`). Importação de planilha e sincronização SIPROV **não** aplicam cupom.
- **Devolução da vaga** (`TenantPlanoCotaService::devolver`): marca `cupom_usos.estornado_em` e decrementa `usos`, para o cupom voltar a ficar disponível (ver a decisão 4 da §9).
- **Comissão:** `TenantPlano::comissaoVenda()` ganha o parâmetro `?float $valorVenda`. Com isso, `DesempenhoService` e `TenantDashboardService` passam a refletir o desconto, porque leem o snapshot `comissao` gravado na venda.

---

## 6. Telas, rotas e permissões

**Central (admin)**: módulo `financeiro`, aba **Cupons**.

| Método | URI | Permissão |
|--------|-----|-----------|
| GET | `/financeiro/cupons` | `cupons.view` |
| POST | `/financeiro/cupons` | `cupons.manage` |
| PUT | `/financeiro/cupons/{cupom}` | `cupons.manage` (não altera `codigo`, `tipo` nem `valor` depois do primeiro uso) |
| PUT | `/financeiro/cupons/{cupom}/status` | `cupons.manage` (pausar, reativar, cancelar) |
| GET | `/financeiro/cupons/{cupom}` | `cupons.view` (detalhe + histórico de usos) |

**Tenant (parceiro)**: tela "Cupons" no painel com as mesmas ações, **sempre filtrada por `tenant_id = tenant()`**, criando cupons com `tenant_id` fixo. A permissão `cupons.manage` vai para o banco do tenant (`TenantRolePermissionsSeeder` + migration de tenant).

**Validação pública**: `POST /f/{slug}/cupom` com `throttle:10,1`. Responde só "válido / inválido + desconto", sem revelar se o código existe em outro parceiro.

Front no padrão da casa: lista + formulário Create/Edit simples com 100% de largura (`CupomForm` + `useCupomForm`), campo "Cupom" com botão "Aplicar" nos dois formulários de venda.

---

## 7. Anti-fraude (mínimo)

- Gerador de código aleatório com 8 caracteres sem ambíguos (`A-Z2-9`, sem `O0I1`), com opção de código manual (campanhas).
- Throttle no endpoint público e log de tentativas inválidas (`Log::warning` com IP e slug).
- Limite por CPF + `lockForUpdate` impedem o uso duplo em corrida.
- Mudanças em cupom auditadas pelo `owen-it`; usos na tabela própria (não na auditoria).

---

## 8. Ordem de implementação e testes

Encaixa no plano de [desenvolvimento.md](desenvolvimento.md) **depois da F1 (Comissões)**, porque o cupom altera o valor da comissão e a F1 cria o registro onde isso fica.

| # | Tarefa | Testes (Feature) |
|---|--------|------------------|
| C1 | Migrations + models `Cupom`, `CupomPlano`, `CupomUso` + `situacao()` | Situação calculada: agendado, expirado, esgotado, pausado |
| C2 | `CupomService::validar` | Uma falha por regra da §4; cupom de outro tenant é rejeitado; arredondamento do percentual |
| C3 | `CupomService::aplicar` na transação do vínculo + snapshot na auditoria e na comissão | Venda com cupom grava `valor_final` e comissão sobre ele; o limite total não estoura em duas aplicações seguidas; a falha do cupom não consome a vaga |
| C4 | Estorno na devolução da vaga | Devolver a vaga libera o uso |
| C5 | CRUD central + permissões | 403 sem `cupons.manage`; não edita valor depois de usado |
| C6 | CRUD no tenant | O tenant só vê e cria cupons próprios |
| C7 | Campo "Cupom" no cadastro manual e no formulário público + endpoint com throttle | 429 após 10 tentativas por minuto |
| C8 | Relatório: usos por cupom, desconto total e impacto na comissão (aba Cupons e exportação) | — |

---

## 9. Decisões de negócio necessárias

1. **Alvo:** cupom de venda (A), de fatura (B) ou os dois?
2. **Quem cria:** só o admin ou também o parceiro? O parceiro pode criar cupom de qualquer valor ou há um teto (ex.: até 20%)?
3. **Comissão:** é calculada sobre o valor cheio ou sobre o valor com desconto?
4. **Devolução:** quando o beneficiário sai, o uso do cupom é devolvido (volta a contar no limite)?
5. **`trial_estendido`:** fica fora até existir cobrança recorrente?
6. **Seções 3 a 13 do documento original:** se existir uma versão completa, ela deve ser revisada contra esta proposta antes de implementar.
