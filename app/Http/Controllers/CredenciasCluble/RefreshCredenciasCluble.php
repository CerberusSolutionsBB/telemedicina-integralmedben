<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use App\Models\CredenciasCluble;
use App\Services\ClubleAuthService;
use Illuminate\Http\RedirectResponse;

class RefreshCredenciasCluble extends Controller
{
    public function __invoke(CredenciasCluble $credencial): RedirectResponse
    {
        app(ClubleAuthService::class)->refreshToken($credencial);

        return redirect()
            ->route('configuracoes.credencias_cluble.index')
            ->with('success', 'Token atualizado com sucesso!');
    }
}