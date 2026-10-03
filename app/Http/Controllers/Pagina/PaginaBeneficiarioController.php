<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use App\Support\BeneficiarioPermissoes;
use Illuminate\Http\Request;

class PaginaBeneficiarioController extends Controller
{
    /**
     * Ações do CRUD de beneficiários habilitadas para o parceiro.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $regras = collect(BeneficiarioPermissoes::ACOES)->map(fn () => ['required', 'boolean'])->all();
        $permissoes = array_map('boolval', $request->validate($regras));

        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenant->id]);
        $detail->update(['configuracao' => [
            ...($detail->configuracao ?? []),
            BeneficiarioPermissoes::CHAVE => $permissoes,
        ]]);

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('message', 'Permissões de beneficiário atualizadas com sucesso.')
            ->with('type', 'success');
    }
}
