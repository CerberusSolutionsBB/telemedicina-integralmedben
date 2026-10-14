<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Cupom\CupomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaginaCupomController extends Controller
{
    /**
     * Teto de desconto (%) dos cupons que o parceiro cria. Vazio = parceiro sem cupons.
     */
    public function teto(Request $request, Tenant $tenant, CupomService $cupomService): RedirectResponse
    {
        $dados = $request->validate(['teto' => ['nullable', 'numeric', 'min:0', 'max:100']]);

        $cupomService->definirTeto($tenant->id, isset($dados['teto']) ? (float) $dados['teto'] : null);

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('message', 'Teto de desconto dos cupons atualizado.')
            ->with('type', 'success');
    }
}
