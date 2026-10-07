<?php

namespace App\Http\Services\Dashboard;

use App\Enums\SmsStatusEnum;
use App\Http\Services\Patient\PatientFiltros;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Planos;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardService
{
    // Plano interno (config/planos.php) com seção própria no dashboard.
    private const PLANO_BENEFICIOS = 'beneficios';

    public function __construct(private TenantPlanoCotaService $planoCotaService) {}

    public function getData(int $year, ?string $monthParam = null): array
    {
        $selectedMonth = $monthParam ? (int) $monthParam : null;
        $currentMonth = now()->month;

        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        // KPIs
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::whereNull('deleted_at')->where('status', true)->count();

        // Páginas/parceiros + beneficiários (banco do tenant): total, ativos, inativos,
        // sem plano e cadastros por mês. Base dos KPIs, gráficos e desempenho.
        $pages = [];
        $tenantMonthlyGrowth = [];
        $globalMonthly = array_fill(0, 12, 0);
        $totalPatients = 0;
        $patientsWithoutPlan = 0;

        foreach (Tenant::whereNull('deleted_at')->with('details')->orderBy('id')->get() as $tenant) {
            $beneficiarios = $this->beneficiariosDoTenant($tenant, $year);
            $meses = $beneficiarios['meses'];

            foreach ($meses as $mes => $total) {
                $globalMonthly[$mes] += $total;
            }

            $tenantMonthlyGrowth[$tenant->id] = $meses;
            $totalPatients += $beneficiarios['total'];
            $patientsWithoutPlan += $beneficiarios['sem_plano'];

            $pages[] = [
                'id' => $tenant->id,
                'name' => $tenant->details->first()?->descricao ?? ($tenant->tenant_domain ?? $tenant->id),
                'subdomain' => $tenant->tenant_domain,
                'url' => $tenant->url,
                'patients' => $selectedMonth ? $meses[$selectedMonth - 1] : array_sum($meses),
                'status' => $tenant->status,
                'cor' => $tenant->indicativo_cor,
                'ativos' => $beneficiarios['ativos'],
                'inativos' => $beneficiarios['inativos'],
            ];
        }

        $monthlyGrowth = collect(range(1, 12))->map(fn ($m) => [
            'label' => $labels[$m - 1],
            'value' => $globalMonthly[$m - 1],
        ])->toArray();

        $newThisMonth = $globalMonthly[$currentMonth - 1];

        // Planos configurados nas Páginas de Parceiros: valor, cota e vidas em uso.
        $nomesPlanos = collect(Planos::options())->pluck('label', 'value');
        $planosParceiros = TenantPlano::whereIn('tenant_id', collect($pages)->pluck('id'))
            ->get(['tenant_id', 'cod_plano', 'quantidade', 'valor'])
            ->groupBy('tenant_id')
            ->flatMap(function ($planos, $tenantId) use ($nomesPlanos) {
                $uso = $this->planoCotaService->uso($tenantId);

                return $planos->map(fn (TenantPlano $plano) => [
                    'tenant_id' => $tenantId,
                    'cod_plano' => (string) $plano->cod_plano,
                    'plano' => $nomesPlanos[(string) $plano->cod_plano] ?? "Plano {$plano->cod_plano}",
                    'valor' => $plano->valor !== null ? (float) $plano->valor : null,
                    'quantidade' => $plano->quantidade,
                    'em_uso' => (int) ($uso[$plano->cod_plano] ?? 0),
                ]);
            })
            ->values()
            ->toArray();

        // Plano de Benefícios: contrato por parceiro + beneficiários vinculados.
        $tenantIds = collect($pages)->pluck('id');

        $beneficiariosMes = TenantPlanoBeneficiario::where('cod_plano', self::PLANO_BENEFICIOS)
            ->whereIn('tenant_id', $tenantIds)
            ->whereYear('created_at', $year)
            ->selectRaw('tenant_id, MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('tenant_id', 'month')
            ->get()
            ->groupBy('tenant_id')
            ->map(function ($rows) {
                $meses = array_fill(0, 12, 0);
                foreach ($rows as $row) {
                    $meses[$row->month - 1] = (int) $row->total;
                }

                return $meses;
            });

        $beneficiariosTotal = TenantPlanoBeneficiario::where('cod_plano', self::PLANO_BENEFICIOS)
            ->whereIn('tenant_id', $tenantIds)
            ->selectRaw('tenant_id, COUNT(*) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $planoBeneficios = [
            'nome' => config('planos.internos.'.self::PLANO_BENEFICIOS, 'Plano de Benefícios'),
            'parceiros' => TenantPlano::where('cod_plano', self::PLANO_BENEFICIOS)
                ->whereIn('tenant_id', $tenantIds)
                ->get(['tenant_id', 'quantidade', 'saldo', 'valor'])
                ->map(fn (TenantPlano $plano) => [
                    'tenant_id' => $plano->tenant_id,
                    'quantidade' => $plano->quantidade,
                    'disponivel' => max(0, $plano->saldo),
                    'valor' => $plano->valor !== null ? (float) $plano->valor : null,
                    'beneficiarios' => (int) ($beneficiariosTotal[$plano->tenant_id] ?? 0),
                    'novos_por_mes' => $beneficiariosMes[$plano->tenant_id] ?? array_fill(0, 12, 0),
                ])
                ->values()
                ->toArray(),
        ];

        // Ranking top páginas por pacientes
        $topPages = collect($pages)
            ->sortByDesc('patients')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'totalPatients' => $totalPatients,
            'patientsWithoutPlan' => $patientsWithoutPlan,
            'newThisMonth' => $newThisMonth,
            'monthlyGrowth' => $monthlyGrowth,
            'currentYear' => $year,
            'currentMonth' => $currentMonth,
            'selectedMonth' => $selectedMonth,
            'pages' => $pages,
            'topPages' => $topPages,
            'tenantMonthlyGrowth' => $tenantMonthlyGrowth,
            'monthLabels' => $labels,
            'planosParceiros' => $planosParceiros,
            'planoBeneficios' => $planoBeneficios,
            'smsFailed' => SmsLogs::where('status', SmsStatusEnum::Failed)->whereYear('created_at', $year)->count(),
            'updatedAt' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Panorama de beneficiários do parceiro (banco do próprio tenant): só conta
     * quem tem plano vinculado — total, ativos, inativos e cadastros por mês do ano.
     * Quem não tem plano entra em "sem_plano". Banco indisponível não derruba o dashboard.
     *
     * @return array{total: int, ativos: int, inativos: int, sem_plano: int, meses: array<int, int>}
     */
    private function beneficiariosDoTenant(Tenant $tenant, int $year): array
    {
        try {
            return $tenant->run(function () use ($tenant, $year) {
                $cpfs = PatientFiltros::cpfsComPlanoFormatos($tenant->id);
                $comPlano = Patient::whereIn('cpf', $cpfs);

                $total = (clone $comPlano)->count();
                $ativos = (clone $comPlano)->where('status', true)->count();

                $porMes = (clone $comPlano)
                    ->whereYear('created_at', $year)
                    ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month');

                $meses = array_fill(0, 12, 0);
                foreach ($porMes as $mes => $totalMes) {
                    $meses[$mes - 1] = (int) $totalMes;
                }

                return [
                    'total' => $total,
                    'ativos' => $ativos,
                    'inativos' => $total - $ativos,
                    'sem_plano' => max(0, Patient::count() - $total),
                    'meses' => $meses,
                ];
            });
        } catch (Throwable $e) {
            // run() não desfaz a troca de banco quando falha: volta para o central.
            tenancy()->end();

            Log::warning("Beneficiários indisponíveis para o tenant {$tenant->id}: {$e->getMessage()}");

            return ['total' => 0, 'ativos' => 0, 'inativos' => 0, 'sem_plano' => 0, 'meses' => array_fill(0, 12, 0)];
        }
    }
}
