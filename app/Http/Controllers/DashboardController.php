<?php

namespace App\Http\Controllers;

use App\Http\Services\Dashboard\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        return Inertia::render('Dashboard', $this->dashboardService->getData($year));
    }
}
