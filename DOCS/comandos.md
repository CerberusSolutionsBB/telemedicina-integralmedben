# Comandos Artisan

Rodar sempre na raiz do projeto (onde fica o arquivo `artisan`):

```bash
cd /caminho/do/projeto
php artisan <comando> [opções]
```

Para ver a ajuda de qualquer comando: `php artisan <comando> --help`.

---

## `siprov:associados`

Lista os **associados com benefício na SIPROV** (os mesmos da tela Telemedicina) e leva o plano para a
**Página do Parceiro**, casando pelo CPF:

- **Plano preenchido**: o parceiro tem o vínculo do associado, mas gravado **sem plano** → recebe o plano da SIPROV.
- **Vínculo criado**: o parceiro tem o **paciente** com o mesmo CPF (ex.: cadastrado pelo formulário dinâmico),
  mas **nenhum vínculo de plano** → o vínculo é criado com o plano da SIPROV (igual ao vínculo feito pela
  Página do Parceiro). É o caso do beneficiário que aparece como "Sem plano" no parceiro e com plano na SIPROV.

### Opções

| Opção | Padrão | Descrição |
|---|---|---|
| `--simular` | — | Só mostra o que seria atualizado, **não grava nada**. |
| `--situacao=` | `Todos` | Situação do benefício na SIPROV: `ATIVO`, `INATIVO` ou `Todos`. |

### Exemplos

```bash
# 1. Conferir primeiro (recomendado), sem alterar nada
php artisan siprov:associados --simular

# 2. Listar e atualizar os vínculos sem plano
php artisan siprov:associados

# Somente associados com benefício ativo
php artisan siprov:associados --situacao=ATIVO --simular
```

### O que aparece na saída

1. Total e tabela dos associados da SIPROV: Nome, CPF, Benefício, Situação, Plano(s).
2. Tabela dos beneficiários de parceiro atualizados (ou que seriam, com `--simular`): Ação
   (`Plano preenchido` / `Vínculo criado`), Parceiro, Nome, CPF, Plano. Se não houver nenhum:
   `Nenhum parceiro com beneficiário sem plano para atualizar.`

### Regras

- O CPF é comparado só pelos dígitos (com ou sem máscara).
- Vínculos que já têm plano não são alterados.
- Não cria vínculo quando o parceiro já tem um para o CPF (telemedicina ou plano interno).
- Associados da SIPROV sem plano são ignorados.
- Grava `cod_plano`, `cod_planos`, `plano_label` e o `codBeneficio` (quando estiver vazio).
- **Não consome vaga** do plano do parceiro (o saldo não muda).
- Cada alteração fica registrada na auditoria do vínculo.
- Se a SIPROV estiver fora do ar, o comando termina com erro e nada é alterado.

---

## `sms:send`

Envia um SMS de teste pela Devyx e mostra o retorno do provedor.

```bash
php artisan sms:send 85999999999 "Mensagem de teste"

# Sem a mensagem, ela é pedida no terminal
php artisan sms:send 85999999999
```

---

## `storage:check-permissions`

Verifica as permissões dos diretórios/arquivos de `storage` e corrige quando necessário.

```bash
# Só reportar os problemas
php artisan storage:check-permissions --dry-run

# Reportar e corrigir
php artisan storage:check-permissions
```

---

## `latex:check`

Verifica se o ambiente LaTeX (usado nos PDFs) está configurado corretamente no servidor.

```bash
php artisan latex:check
```
