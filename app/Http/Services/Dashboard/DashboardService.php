<?php

namespace App\Http\Services\Dashboard;

use App\Models\CentralPatient;
use App\Models\Question;
use App\Models\Tenant;

class DashboardService
{
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
        $pages = Tenant::whereNull('deleted_at')
            ->withCount(['centralPatients' => function ($q) use ($year, $selectedMonth) {
                if ($selectedMonth) {
                    $q->whereYear('created_at', $year)
                      ->whereMonth('created_at', $selectedMonth);
                }
            }])
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
            ])
            ->toArray();

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
        ];
    }
}
