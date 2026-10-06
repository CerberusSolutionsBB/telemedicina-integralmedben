<?php

namespace App\Http\Controllers;

use App\Http\Services\Dashboard\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class FinancieroController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(): Response
    {
        return Inertia::render('Financiero/Index', $this->dashboardService->getData(now()->year));
    }
}
