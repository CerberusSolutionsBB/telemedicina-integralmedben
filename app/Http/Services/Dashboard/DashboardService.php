<?php

namespace App\Http\Services\Dashboard;

use App\Enums\SmsStatusEnum;
use App\Models\CentralPatient;
use App\Models\Question;
use App\Models\SmsLogs;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Planos;

class DashboardService
{
    // Plano interno (config/planos.php) com seção própria no dashboard.
    private const PLANO_BENEFICIOS = 'beneficios';

    public function __construct(private TenantPlanoCotaService $planoCotaService) {}

    public function getData(int $year, ?string $monthParam = null): array
    {
        $selectedMonth = $monthParam ? (int) $monthParam : null;
        $currentMonth = now()->month;

        $planQuestion = Question::where('role', 'plan')->first();
        $planQuestionId = $planQuestion?->id;

        // KPIs
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::whereNull('deleted_at')->where('status', true)->count();
        $totalPatients = CentralPatient::count();
        $patientsWithPlan = $planQuestionId
            ? CentralPatient::whereHas('answers', fn ($q) => $q->where('question_id', $planQuestionId)->whereNotNull('answer')->where('answer', '!=', ''))->count()
            : 0;
        $newThisMonth = CentralPatient::whereYear('created_at', $year)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        // Gráfico mensal
        $monthly = CentralPatient::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $monthlyGrowth = collect(range(1, 12))->map(fn ($m) => [
            'label' => $labels[$m - 1],
            'value' => (int) ($monthly[$m] ?? 0),
        ])->toArray();

        // Dados mensais por tenant (para filtro no gráfico)
        $perTenantMonthly = CentralPatient::selectRaw('tenant_id, MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('tenant_id', 'month')
            ->get()
            ->groupBy('tenant_id');

        $tenantMonthlyGrowth = [];
        foreach ($perTenantMonthly as $tenantId => $rows) {
            $data = array_fill(0, 12, 0);
            foreach ($rows as $row) {
                $data[$row->month - 1] = $row->total;
            }
            $tenantMonthlyGrowth[$tenantId] = $data;
        }

        // Lista de páginas/tenants
        $periodo = function ($q) use ($year, $selectedMonth) {
            $q->whereYear('created_at', $year);
            if ($selectedMonth) {
                $q->whereMonth('created_at', $selectedMonth);
            }
        };

        $pages = Tenant::whereNull('deleted_at')
            ->withCount(['centralPatients' => $periodo])
            ->with('details')
            ->orderBy('id')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->details->first()?->descricao ?? ($t->tenant_domain ?? $t->id),
                'subdomain' => $t->tenant_domain,
                'url' => $t->url,
                'patients' => $t->central_patients_count ?? 0,
                'status' => $t->status,
                'cor' => $t->indicativo_cor,
            ])
            ->toArray();

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
            'patientsWithPlan' => $patientsWithPlan,
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
}
