<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCredenciasClubleRequest;
use App\Services\ClubleAuthService;
use Illuminate\Http\RedirectResponse;

class StoreCredenciasClubleController extends Controller
{
    public function __construct(
        protected ClubleAuthService $clubleAuthService
    ) {}
    public function __invoke(StoreCredenciasClubleRequest $request): RedirectResponse
    {
        $validated            = $request->validated();
        $validated['user_id'] = auth()->id();
        $credencial           = $this->clubleAuthService->createAndAuthenticate($validated);
        if (! $credencial) {
            return back()
                ->withInput()
                ->withErrors(['api_error' => 'Não foi possível autenticar na API do Clube. Verifique as credenciais.']);
        }
        return redirect()
            ->route('configuracoes.credencias_cluble.index')
            ->with('success', "Credencial '{$credencial->title}' criada e autenticada com sucesso!");
    }
}
