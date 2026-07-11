<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class PaginaStatusController extends Controller
{
    public function __invoke(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => ! $tenant->status]);

        return back()->with('message', 'Status atualizado com sucesso!')->with('type', 'success');
    }
}
