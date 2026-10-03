<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Http\Services\Patient\PatientFiltros;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Support\Planos;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PaginaIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $plano = (string) $request->input('plano', '');
        $tenantId = (string) $request->input('tenant', '');
        $search = trim((string) $request->input('search', ''));

        $tenants = Tenant::with('details')
            ->when($tenantId !== '', fn ($q) => $q->whereKey($tenantId))
            // Busca por ID, domínio ou nome/código da página.
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('id', 'like', "%{$search}%")
                ->orWhereHas('domains', fn ($d) => $d->where('domain', 'like', "%{$search}%"))
                ->orWhereHas('details', fn ($d) => $d
                    ->where('descricao', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"))))
            ->when($plano !== '', fn ($q) => $q->whereIn('id', TenantPlano::where('cod_plano', $plano)->select('tenant_id')))
            ->paginate(10)
            ->withQueryString();

        $labels = collect(Planos::options())->pluck('label', 'value');
        $contratos = TenantPlano::all();
        $contratados = $contratos->groupBy('tenant_id');

        // Beneficiários de todos os parceiros (uma consulta por banco de tenant).
        $todos = Tenant::with('details')->get();
        $beneficiarios = $todos->mapWithKeys(fn (Tenant $t) => [$t->id => $this->beneficiarios($t)]);

        $tenants->getCollection()->each(function (Tenant $tenant) use ($labels, $contratados, $beneficiarios) {
            $tenant->setAttribute('planos_contratados', ($contratados[$tenant->id] ?? collect())
                ->map(fn (TenantPlano $p) => [
                    'value' => (string) $p->cod_plano,
                    'label' => $labels[$p->cod_plano] ?? "Plano {$p->cod_plano}",
                    'quantidade' => $p->quantidade,
                ])
                ->values());
            $tenant->setAttribute('beneficiarios', $beneficiarios[$tenant->id] ?? null);
        });

        return Inertia::render('Pagina/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'plano', 'tenant']),
            'tenantsOpcoes' => $todos
                ->map(fn (Tenant $t) => ['value' => $t->id, 'label' => $t->details->first()?->descricao ?: $t->details->first()?->sigla ?: $t->id])
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
            'planos' => collect(Planos::options())->map(fn ($p) => ['value' => $p['value'], 'label' => $p['label']])->all(),
            'totais' => $this->totais($tenantId, $plano, $contratos, $beneficiarios),
        ]);
    }

    /**
     * Cards: status dos beneficiários e contratos dos parceiros do filtro; por plano
     * respeita só o filtro de parceiro (o card também é o filtro de plano).
     *
     * @return array{contratos: int, total: int, ativos: int, inativos: int, planos: array<int, array{value: string, label: string, parceiros: int, vagas: int, beneficiarios: int}>}
     */
    private function totais(string $tenantId, string $plano, Collection $contratos, Collection $beneficiarios): array
    {
        if ($tenantId !== '') {
            $contratos = $contratos->where('tenant_id', $tenantId);
            $beneficiarios = $beneficiarios->only([$tenantId]);
        }

        $doFiltro = $plano === ''
            ? $beneficiarios->keys()
            : $contratos->where('cod_plano', $plano)->pluck('tenant_id')->unique();

        $filtrados = $beneficiarios->only($doFiltro->all())->filter();

        $planos = collect(Planos::options())
            ->map(function (array $p) use ($contratos, $beneficiarios) {
                $doPlano = $contratos->where('cod_plano', $p['value']);

                return [
                    'value' => $p['value'],
                    'label' => $p['label'],
                    'parceiros' => $doPlano->pluck('tenant_id')->unique()->count(),
                    'vagas' => (int) $doPlano->sum('quantidade'),
                    'beneficiarios' => $beneficiarios->filter()->sum(
                        fn (array $b) => collect($b['planos'])->firstWhere('value', $p['value'])['total'] ?? 0
                    ),
                ];
            })
            ->filter(fn (array $p) => $p['parceiros'] > 0 || $p['beneficiarios'] > 0)
            ->values()
            ->all();

        return [
            'contratos' => $contratos->whereIn('tenant_id', $doFiltro)->count(),
            'total' => $filtrados->sum('total'),
            'ativos' => $filtrados->sum('ativos'),
            'inativos' => $filtrados->sum('inativos'),
            'planos' => $planos,
        ];
    }

    /**
     * Totais de beneficiários do parceiro (cadastrados, ativos, inativos e por plano).
     * Banco do tenant indisponível não derruba a listagem.
     */
    private function beneficiarios(Tenant $tenant): ?array
    {
        try {
            return $tenant->run(fn () => PatientFiltros::totais($tenant->id, []));
        } catch (Throwable $e) {
            Log::warning("Totais de beneficiários indisponíveis para o tenant {$tenant->id}: {$e->getMessage()}");

            return null;
        }
    }
}
