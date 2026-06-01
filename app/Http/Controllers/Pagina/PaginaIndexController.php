<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaginaIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenants = Tenant::with(['details', 'details.user'])->paginate(10);

        return Inertia::render('Pagina/Index', [
            'tenants' => $tenants,
        ]);
    }
}
