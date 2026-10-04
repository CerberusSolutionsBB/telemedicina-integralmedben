<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Services\Dashboard\TenantDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantDashboardService $dashboardService): Response
    {
        $year = (int) $request->input('year', now()->year);
        $month = $request->integer('month') ?: null;

        return Inertia::render('Tenant/Dashboard/Index', $dashboardService->getData(tenant(), $year, $month));
    }
}
