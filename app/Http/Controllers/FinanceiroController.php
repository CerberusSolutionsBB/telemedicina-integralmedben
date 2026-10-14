<?php

namespace App\Http\Controllers;

use App\Http\Services\Dashboard\DashboardService;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class FinanceiroController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(): Response
    {
        // Só o que a tela usa; o cálculo próprio do financeiro vem com as faturas (F2).
        return Inertia::render('Financeiro/Index', Arr::only(
            $this->dashboardService->getData(now()->year),
            ['pages', 'planosParceiros', 'updatedAt'],
        ));
    }
}
