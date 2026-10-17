<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\ModulosParceiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaginaModuloController extends Controller
{
    /**
     * Liga ou desliga um módulo opcional do painel do parceiro (aba Configuração).
     */
    public function update(Request $request, Tenant $tenant, string $modulo): RedirectResponse
    {
        validator(['modulo' => $modulo], ['modulo' => ['required', Rule::in(array_keys(ModulosParceiro::MODULOS))]])->validate();
        $dados = $request->validate(['habilitado' => ['required', 'boolean']]);

        ModulosParceiro::definir($tenant->id, $modulo, (bool) $dados['habilitado']);

        $nome = ModulosParceiro::MODULOS[$modulo]['label'];

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('success', "{$nome} ".($dados['habilitado'] ? 'habilitado' : 'desabilitado').' para este parceiro.');
    }
}
