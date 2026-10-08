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
            'planos.*.valor' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'planos.*.comissao_tipo' => ['nullable', Rule::in(array_keys(TenantPlano::COMISSOES))],
            'planos.*.comissao_valor' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'planos.*.cod_plano.in' => 'Plano inválido.',
            'planos.*.cod_plano.distinct' => 'Plano repetido.',
            'planos.*.quantidade.required' => 'Informe a quantidade.',
            'planos.*.quantidade.min' => 'A quantidade deve ser no mínimo 1.',
            'planos.*.quantidade.max' => 'A quantidade deve ser no máximo 1.000.000.',
            'planos.*.valor.numeric' => 'Informe um valor válido.',
            'planos.*.valor.min' => 'O valor não pode ser negativo.',
            'planos.*.valor.max' => 'O valor é muito alto.',
            'planos.*.comissao_tipo.in' => 'Tipo de comissão inválido.',
            'planos.*.comissao_valor.numeric' => 'Informe um valor de comissão válido.',
            'planos.*.comissao_valor.min' => 'A comissão não pode ser negativa.',
            'planos.*.comissao_valor.max' => 'A comissão é muito alta.',
        ]);

        // Percentual não pode passar de 100%.
        foreach ($validated['planos'] as $i => $plano) {
            if (($plano['comissao_tipo'] ?? null) === TenantPlano::COMISSAO_PERCENTUAL
                && (float) ($plano['comissao_valor'] ?? 0) > 100) {
                throw ValidationException::withMessages([
                    "planos.{$i}.comissao_valor" => 'O percentual de comissão não pode ser maior que 100%.',
                ]);
            }
        }

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

                // Via model para a alteração do valor ser auditada.
                TenantPlano::where('tenant_id', $tenant->id)
                    ->where('cod_plano', $plano['cod_plano'])
                    ->first()
                    ?->update([
                        'valor' => $plano['valor'] ?? null,
                        'comissao_tipo' => $plano['comissao_tipo'] ?? null,
                        'comissao_valor' => $plano['comissao_valor'] ?? null,
                    ]);
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
