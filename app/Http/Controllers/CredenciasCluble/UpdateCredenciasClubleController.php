<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCredenciasClubleRequest;
use App\Models\CredenciasCluble;
use App\Services\ClubleAuthService;
use Illuminate\Http\RedirectResponse;

class UpdateCredenciasClubleController extends Controller
{
    public function __construct(
        protected ClubleAuthService $clubleAuthService
    ) {}

    public function __invoke(UpdateCredenciasClubleRequest $request, int $id): RedirectResponse
    {
        $credencial = CredenciasCluble::findOrFail($id);
        $validated  = $request->validated();

        // Se alterou client_secret ou scope, reautentica na API
        if (! empty($validated['client_secret']) || $this->scopeChanged($credencial, $validated)) {
            $authData = [
                'user_id'       => $credencial->user_id,
                'title'         => $validated['title'] ?? $credencial->title,
                'grant_type'    => $credencial->grant_type,
                'client_id'     => $credencial->client_id,
                'client_secret' => $validated['client_secret'] ?? $credencial->client_secret,
                'scope'         => $validated['scope'] ?? $credencial->scope,
            ];

            $novaCredencial = $this->clubleAuthService->createAndAuthenticate($authData);

            if (! $novaCredencial) {
                return back()
                    ->withInput()
                    ->withErrors(['api_error' => 'Falha ao reautenticar na API do Clube. Verifique as credenciais.']);
            }

            // Remove a credencial antiga (soft delete)
            $credencial->delete();

            $message = "Credencial '{$novaCredencial->title}' atualizada e reautenticada!";
        } else {
            // Apenas atualiza o título
            $credencial->update([
                'title' => $validated['title'] ?? $credencial->title,
            ]);

            $message = "Credencial '{$credencial->title}' atualizada!";
        }

        return redirect()
            ->route('configuracoes.credencias_cluble.index')
            ->with('success', $message);
    }

    private function scopeChanged(CredenciasCluble $credencial, array $validated): bool
    {
        if (! isset($validated['scope'])) {
            return false;
        }

        return $validated['scope'] !== $credencial->scope;
    }
}
