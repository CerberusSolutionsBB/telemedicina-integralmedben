# Análise de Arquitetura - Projeto API

**Data da análise:** 02/06/2026  
**Projeto:** API - Sistema de Solicitações  
**Branch atual:** inova-0003  

---

## 📊 1. INFORMAÇÕES GERAIS

| Item | Valor |
|------|-------|
| Laravel Versão | $(php artisan --version 2>/dev/null | cut -d' ' -f2) |
| PHP Versão | $(php -v 2>/dev/null | head -1 | cut -d' ' -f2) |
| Autenticação | Laravel Sanctum |
| Ambiente | Desenvolvimento |

---

## 🗺️ 2. ROTAS DA API

### Total de rotas identificadas: XX

### Módulo de Autenticação (Auth)
| Método | URI | Controller |
|--------|-----|------------|
| POST | api/login | AuthController@login |
| POST | api/logout | AuthController@logout |
| POST | api/register | AuthController@register |
| GET | api | AuthController@preview |
| GET | user/index/{id} | AuthController@userPeloId |
| DELETE | user/delete/{id} | AuthController@deleteUser |
| PUT | user/update-acesso/{id} | AuthController@updateAcesso |
| POST | user/vincular | AuthController@vincularUsuario |

### Módulo de Configuração
| Método | URI | Controller |
|--------|-----|------------|
| POST | configuracao/acesso | ConfiguracaoController@acesso |
| GET | configuracao/categorias | ListaDeCategoriaController |
| POST | configuracao/meio-ambiente | ConfiguracaoController@meioAmbiente |
| GET | configuracao/response | ListaResponseController |
| GET | configuracao/roles | ListaDeRoleController |
| GET | configuracao/setores | SetorController@index |
| POST | configuracao/setores | SetorController@store |
| GET | configuracao/setores/{id} | SetorController@show |
| PUT | configuracao/setores/{id} | SetorController@update |
| DELETE | configuracao/setores/{id} | SetorController@destroy |
| GET | configuracao/subcategorias | ListaDeSubcategoriaController |
| GET | configuracao/tipos | ListaDeTipoController |
| GET | configuracao/users | UserController@index |
| POST | configuracao/users | UserController@store |
| GET | configuracao/users/{id} | UserController@show |
| PUT | configuracao/users/{id} | UserController@update |
| DELETE | configuracao/users/{id} | UserController@destroy |
| GET | configuracao/usuarios | ListaUsuarioController |

### Módulo de Solicitações
| Método | URI | Controller |
|--------|-----|------------|
| POST | solicitacao/create | SolicitacaoController@create |
| GET | solicitacao/{id} | SolicitacaoController@solicitacao |
| GET | solicitacao/all/{userId} | SolicitacaoController@all |
| GET | solicitacao/codigo/{codigo} | SolicitacaoController@buscarPorCodigo |
| GET | solicitacao/status/{status} | SolicitacaoController@solicitacaoPorStatus |
| POST | solicitacao/enviar | SolicitacaoController@enviar |
| DELETE | solicitacao/delete | SolicitacaoController@delete |
| GET | solicitacao/images/{id} | SolicitacaoController@images |
| GET | solicitacao/gerar-liberacao/{id} | SolicitacaoController@gerarLiberacao |
| POST | execucao/create | SolicitacaoExecucaoController@store |

### Módulo de Liberação
| Método | URI | Controller |
|--------|-----|------------|
| GET | liberacao | LiberacaoController@index |
| POST | liberacao | LiberacaoController@create |
| POST | liberacao/status | LiberacaoController@createPorStatus |
| GET | arquivo/lieracao-assiada/{id} | ArquivoController@liberacaoAssinada |
| DELETE | arquivo/{id} | ArquivoController@delete |

### Módulo de Dashboard
| Método | URI | Controller |
|--------|-----|------------|
| GET | dashboard/diaria/{data} | DashboardController@diaria |
| GET | dashboard/grafico-mes/{data} | DashboardController@graficoData |
| GET | dashboard/status | DashboardController@status |

### Módulo de Perfil
| Método | URI | Controller |
|--------|-----|------------|
| GET | perfil | ProfileShowController |
| PUT | perfil | ProfileUpdateController |
| PUT | perfil/password | ProfileUpdatePasswordController |

### Módulo de Mapa e Roles
| Método | URI | Controller |
|--------|-----|------------|
| GET | mapa/all | MapaController@all |
| GET | user/{userId}/roles | RoleController@index |

---

## 🎮 3. CONTROLLERS IDENTIFICADOS

### Por módulo

**Auth/**
- AuthController.php (autenticação e usuários)

**Configuracao/**
- ConfiguracaoController.php
- ListaDeCategoriaController.php
- ListaDeSubcategoriaController.php
- ListaDeTipoController.php
- ListaDeRoleController.php
- ListaResponseController.php
- ListaUsuarioController.php
- SetorController.php (CRUD completo)
- UserController.php (CRUD completo)

**Solicitacao/**
- SolicitacaoController.php (múltiplas ações)
- SolicitacaoExecucaoController.php

**Liberacao/**
- LiberacaoController.php
- ArquivoController.php

**Dashboard/**
- DashboardController.php

**Profile/**
- ProfileShowController.php
- ProfileUpdateController.php
- ProfileUpdatePasswordController.php

**Role/**
- RoleController.php

**Mapa/**
- MapaController.php

---

## 📦 4. PADRÕES IDENTIFICADOS

### ✅ Pontos positivos
- **Controllers separados por responsabilidade** (Auth, Configuração, Solicitação, etc.)
- **CRUD completo** para Setor e User
- **Rotas RESTful** bem definidas (index, show, store, update, destroy)

### ⚠️ Observações
- Controllers podem estar **faturados** (muitos métodos por controller)
- Ex: SolicitacaoController tem 10+ métodos
- Ex: AuthController mistura autenticação + gerenciamento de usuários

---

## 🔐 5. SEGURANÇA

### Autenticação
- **Laravel Sanctum** configurado
- Rota `sanctum/csrf-cookie` disponível

### Rotas que precisam de autenticação
- [ ] Verificar quais rotas têm middleware `auth:sanctum`
- [ ] Sugestão: todas rotas exceto `api/login`, `api/register`

### Points to check
- [ ] Implementar rate limiting?
- [ ] Adicionar Policies para autorização?

---

## 🧪 6. TESTES

### Estrutura atual
