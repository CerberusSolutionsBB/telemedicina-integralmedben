<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Planos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaginaPlanoController extends Controller
{
    /**
     * Substitui os planos do tenant pela lista enviada (planos ausentes são removidos).
     */
    public function sync(Request $request, Tenant $tenant, TenantPlanoCotaService $planoCotaService)
    {
        $validated = $request->validate([
            'planos' => ['present', 'array'],
            'planos.*.cod_plano' => ['required', 'string', 'distinct', Rule::in(Planos::codigos())],
            'planos.*.quantidade' => ['required', 'integer', 'min:1', 'max:1000000'],
        ], [
            'planos.*.cod_plano.in' => 'Plano inválido.',
            'planos.*.cod_plano.distinct' => 'Plano repetido.',
            'planos.*.quantidade.required' => 'Informe a quantidade.',
            'planos.*.quantidade.min' => 'A quantidade deve ser no mínimo 1.',
            'planos.*.quantidade.max' => 'A quantidade deve ser no máximo 1.000.000.',
        ]);

        $planos = collect($validated['planos']);

        $this->validarContraUso($planoCotaService->uso($tenant->id), $planos->pluck('quantidade', 'cod_plano')->all());

        DB::connection('mysql')->transaction(function () use ($tenant, $planos, $planoCotaService) {
            // Um a um (e não delete em massa) para a remoção ser auditada.
            TenantPlano::where('tenant_id', $tenant->id)
                ->whereNotIn('cod_plano', $planos->pluck('cod_plano'))
                ->get()
                ->each->delete();

            foreach ($planos as $plano) {
                $planoCotaService->ajustarQuantidade($tenant->id, $plano['cod_plano'], (int) $plano['quantidade']);
            }
        });

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('message', 'Planos atualizados com sucesso.')
            ->with('type', 'success');
    }

    /**
     * Zera a contagem de registrados de um plano (saldo volta ao contratado).
     */
    public function zerar(Tenant $tenant, string $codPlano, TenantPlanoCotaService $planoCotaService)
    {
        $liberadas = $planoCotaService->zerarContagem($tenant->id, $codPlano);

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('message', "Contagem zerada: {$liberadas} vaga(s) liberada(s).")
            ->with('type', 'success');
    }

    /**
     * A quantidade não pode ficar abaixo das vagas já ocupadas, nem um plano em uso pode ser removido.
     *
     * @param  array<string, int>  $uso
     * @param  array<string, int>  $quantidades
     *
     * @throws ValidationException
     */
    private function validarContraUso(array $uso, array $quantidades): void
    {
        $labels = collect(Planos::options())->pluck('label', 'value')->all();
        $erros = [];

        foreach ($uso as $codigo => $emUso) {
            $label = $labels[(string) $codigo] ?? "Plano {$codigo}";
            $quantidade = $quantidades[$codigo] ?? null;

            if ($quantidade === null) {
                $erros[] = "O plano {$label} tem {$emUso} associado(s) vinculado(s) e não pode ser removido.";
            } elseif ($quantidade < $emUso) {
                $erros[] = "O plano {$label} tem {$emUso} associado(s) vinculado(s); a quantidade não pode ser menor que isso.";
            }
        }

        if ($erros) {
            throw ValidationException::withMessages(['planos' => implode(' ', $erros)]);
        }
    }
}
