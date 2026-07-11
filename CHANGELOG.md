# Changelog

## Branch: feature/parceiros-status-siprov-cartao

### Commits

| Hash | Data | Descricao |
|------|------|-----------|
| `bc082bd` | 2026-07-11 | feat: renomear Pagina de Vendas para Pagina de Parceiros |
| `9ff9f5a` | 2026-07-11 | feat: toggle de status para ativar/desativar tenants |
| `c312440` | 2026-07-11 | feat: bloquear login de tenants inativos |
| `efce46b` | 2026-07-11 | feat: gerador de cartao SIPROV via pdflatex + tratamento de erro da API no frontend |
| `ccbe815` | 2026-07-11 | feat: tratamento de erro da API SIPROV no backend |
| `754a823` | 2026-07-11 | feat: script de instalacao do LaTeX (pdflatex) |

---

### Alteracoes por Funcionalidade

#### 1. Renomeacao "Pagina de Vendas" para "Pagina de Parceiros"
- `resources/js/Layouts/CentralAdminLayout.vue` - Sidebar: label renomeado
- `resources/js/Pages/Pagina/Create.vue` - Breadcrumb renomeado
- `resources/js/Pages/Pagina/Show.vue` - Breadcrumb renomeado

#### 2. Toggle de status (Ativar/Desativar Tenant)
- `database/migrations/2026_07_11_173347_add_status_to_tenants_table.php` - Coluna `status` (boolean, default true)
- `app/Http/Controllers/Pagina/PaginaStatusController.php` - Controller para alternar status (PUT)
- `resources/js/Pages/Pagina/Index.vue` - Botao toggle com modal de confirmacao
- `routes/pagina.php` - Rota `pagina.status` (PUT)

#### 3. Bloqueio de login para tenants inativos
- `app/Http/Controllers/Tenant/TenantAuthController.php` - Verifica `tenant()->status` no login e exibicao do formulario
- `resources/js/Pages/Tenant/Login.vue` - Alerta laranja "Parceiro Inativado", formulario desabilitado

#### 4. Gerador de cartao SIPROV via pdflatex
- `app/Http/Controllers/Siprov/SiprovCartaoController.php` - Gera PDF com pdflatex usando template TikZ
- `resources/views/pdf/siprov-cartao.tex` - Template TikZ do cartao (540x300pt)
- `resources/js/Pages/Siprov/Index.vue` - Modal de confirmacao + download do PDF via fetch
- `routes/siprov.php` - Rota `siprov.cartao` (GET)

#### 5. Tratamento de erro da API SIPROV
- `app/Http/Controllers/Siprov/SiprovIndexController.php` - Captura `SiprovException` e retorna `siprovError` ao frontend
- `resources/js/Pages/Siprov/Index.vue` - Banner amigavel "Servico SIPROV Indisponivel" com botao tentar novamente

#### 6. Script de instalacao do LaTeX
- `scripts/install-latex.sh` - Detecta SO (Debian/Fedora/macOS) e instala pdflatex + pacotes necessarios

---

### Arquivos Removidos
- `resources/views/pdf/siprov-cartao.tex.blade.php` - Template blade obsoleto (substituido por .tex)

### Arquivos Ignorados (commit)
- `composer.lock` - Dependencias PHP
- `package-lock.json` - Dependencias JS

### Dependencias do Sistema
- `pdflatex` (TeX Live) - Necessario para geracao de cartoes SIPROV
- Pacotes LaTeX: tikz, xcolor, ifthen, pgfmath, helvet, babel (brazil)
- Para instalar: `sudo scripts/install-latex.sh`
