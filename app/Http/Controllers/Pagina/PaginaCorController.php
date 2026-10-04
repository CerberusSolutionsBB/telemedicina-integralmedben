<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaginaCorController extends Controller
{
    /**
     * Define a cor indicativa do parceiro (null volta à cor padrão do sistema).
     */
    public function __invoke(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'indicativo_cor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'indicativo_cor.regex' => 'Informe uma cor no formato #RRGGBB.',
        ]);

        $cor = $validated['indicativo_cor'] ?? null;

        // Coluna real: update direto para o VirtualColumn não gravar no JSON `data`.
        DB::table('tenants')->where('id', $tenant->id)->update([
            'indicativo_cor' => $cor ? strtoupper($cor) : null,
            'updated_at' => now(),
        ]);

        return back()->with('success', $cor ? 'Cor do parceiro atualizada.' : 'O parceiro voltou a usar a cor padrão.');
    }
}
