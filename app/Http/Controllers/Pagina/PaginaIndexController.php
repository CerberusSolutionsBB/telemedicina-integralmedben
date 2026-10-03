<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Http\Services\Patient\PatientFiltros;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Support\Planos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PaginaIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $plano = (string) $request->input('plano', '');

        $tenants = Tenant::with(['details', 'details.user'])
            ->when($plano !== '', fn ($q) => $q->whereIn('id', TenantPlano::where('cod_plano', $plano)->select('tenant_id')))
            ->paginate(10)
            ->withQueryString();

        $labels = collect(Planos::options())->pluck('label', 'value');
        $contratados = TenantPlano::whereIn('tenant_id', $tenants->pluck('id'))->get()->groupBy('tenant_id');

        $tenants->getCollection()->each(function (Tenant $tenant) use ($labels, $contratados) {
            $tenant->setAttribute('planos_contratados', ($contratados[$tenant->id] ?? collect())
                ->map(fn (TenantPlano $p) => [
                    'value' => (string) $p->cod_plano,
                    'label' => $labels[$p->cod_plano] ?? "Plano {$p->cod_plano}",
                    'quantidade' => $p->quantidade,
                ])
                ->values());
            $tenant->setAttribute('beneficiarios', $this->beneficiarios($tenant));
        });

        return Inertia::render('Pagina/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'plano']),
            'planos' => collect(Planos::options())->map(fn ($p) => ['value' => $p['value'], 'label' => $p['label']])->all(),
        ]);
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
