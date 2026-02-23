<?php

namespace App\Http\Services\Dashboard;

use App\Models\CentralPatient;
use App\Models\Tenant;

class DashboardService
{
    public function getData(): array
    {
        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $monthly = CentralPatient::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyGrowth = collect(range(1, 12))->map(fn($m) => [
            'label' => $labels[$m - 1],
            'value' => (int) ($monthly[$m] ?? 0),
        ])->toArray();

        return [
            'totalTenants'  => Tenant::count(),
            'totalPatients' => CentralPatient::count(),
            'monthlyGrowth' => $monthlyGrowth,
        ];
    }
}
