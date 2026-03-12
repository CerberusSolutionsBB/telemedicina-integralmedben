<?php

namespace App\Http\Services\Dashboard;

use App\Models\CentralPatient;
use App\Models\Question;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(): array
    {
        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $year   = now()->year;
        $month  = now()->month;

        $planQuestion   = Question::where('role', 'plan')->first();
        $planQuestionId = $planQuestion?->id;

        // --- Cards ---
        $totalTenants  = Tenant::count();
        $totalPatients = CentralPatient::count();
        $newThisMonth  = CentralPatient::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $patientsWithPlan = $planQuestionId
            ? CentralPatient::whereHas('answers', fn ($q) =>
                $q->where('question_id', $planQuestionId)->whereNotNull('answer')->where('answer', '!=', '')
              )->count()
            : 0;

        $patientsWithoutPlan = $totalPatients - $patientsWithPlan;

        // --- Crescimento mensal ---
        $monthly = CentralPatient::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyGrowth = collect(range(1, 12))->map(fn ($m) => [
            'label' => $labels[$m - 1],
            'value' => (int) ($monthly[$m] ?? 0),
        ])->toArray();

        // --- Ranking de parceiros (top 8 por total de pacientes) ---
        $tenantCounts = CentralPatient::selectRaw('tenant_id, COUNT(*) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $lastMonthCounts = CentralPatient::selectRaw('tenant_id, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month - 1 ?: 12)
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $tenantRanking = Tenant::get()->map(function ($tenant) use ($tenantCounts, $lastMonthCounts, $planQuestionId) {
            $total     = $tenantCounts[$tenant->id] ?? 0;
            $lastMonth = $lastMonthCounts[$tenant->id] ?? 0;
            $growth    = $lastMonth > 0 ? round((($total - $lastMonth) / $lastMonth) * 100) : 0;

            $withPlan = $planQuestionId
                ? CentralPatient::where('tenant_id', $tenant->id)
                    ->whereHas('answers', fn ($q) =>
                        $q->where('question_id', $planQuestionId)->whereNotNull('answer')->where('answer', '!=', '')
                    )->count()
                : 0;

            return [
                'id'           => $tenant->id,
                'name'         => $tenant->name,
                'subdomain'    => $tenant->tenant_domain,
                'total'        => $total,
                'with_plan'    => $withPlan,
                'without_plan' => $total - $withPlan,
                'growth'       => $growth,
                'is_active'    => !$tenant->deleted_at,
            ];
        })->sortByDesc('total')->values()->take(8)->toArray();

        // --- Distribuição por plano ---
        $planDistribution = [];
        if ($planQuestionId) {
            $planCounts = DB::table('central_patient_answers')
                ->where('question_id', $planQuestionId)
                ->whereNotNull('answer')
                ->where('answer', '!=', '')
                ->select('answer', DB::raw('COUNT(*) as total'))
                ->groupBy('answer')
                ->get();

            $total = $planCounts->sum('total');

            if ($total > 0) {
                $planDistribution = $planCounts
                    ->map(fn ($row) => [
                        'label'      => $row->answer,
                        'value'      => $row->answer,
                        'total'      => $row->total,
                        'percentage' => round(($row->total / $total) * 100),
                    ])
                    ->sortByDesc('total')
                    ->values()
                    ->toArray();
            }
        }

        return [
            'totalTenants'       => $totalTenants,
            'totalPatients'      => $totalPatients,
            'newThisMonth'       => $newThisMonth,
            'patientsWithPlan'   => $patientsWithPlan,
            'patientsWithoutPlan'=> $patientsWithoutPlan,
            'monthlyGrowth'      => $monthlyGrowth,
            'tenantRanking'      => $tenantRanking,
            'planDistribution'   => $planDistribution,
        ];
    }
}
