<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use App\Support\BeneficiarioPermissoes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaginaBeneficiarioController extends Controller
{
    /**
     * Ações do CRUD de beneficiários habilitadas para o parceiro.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $regras = collect(BeneficiarioPermissoes::ACOES)->map(fn () => ['required', 'boolean'])->all();
        $validator = validator($request->all(), $regras);

        if ($validator->fails()) {
            Log::warning('Permissões de beneficiário recusadas', [
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()?->id,
                'erros' => $validator->errors()->toArray(),
                'recebido' => $request->all(),
            ]);
        }

        $permissoes = array_map('boolval', $validator->validate());

        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenant->id]);
        $antes = BeneficiarioPermissoes::doTenant($tenant->id);
        $detail->update(['configuracao' => [
            ...($detail->configuracao ?? []),
            BeneficiarioPermissoes::CHAVE => $permissoes,
        ]]);

        Log::info('Permissões de beneficiário atualizadas', [
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()?->id,
            'antes' => $antes,
            'depois' => $permissoes,
        ]);

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('message', 'Permissões de beneficiário atualizadas com sucesso.')
            ->with('type', 'success');
    }
}
