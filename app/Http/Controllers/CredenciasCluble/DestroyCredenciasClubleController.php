<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use App\Models\CredenciasCluble;
use Illuminate\Http\RedirectResponse;

class DestroyCredenciasClubleController extends Controller
{
    public function __invoke(int $id): RedirectResponse
    {
        try {
            $credencial = CredenciasCluble::findOrFail($id);
            $title      = $credencial->title ?? 'Sem título';

            $credencial->delete();

            return redirect()
                ->route('configuracoes.credencias_cluble.index')
                ->with('success', "Credencial '{$title}' excluída com sucesso!");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('configuracoes.credencias_cluble.index')
                ->with('error', 'Credencial não encontrada.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir credencial. Tente novamente.');
        }
    }
}
