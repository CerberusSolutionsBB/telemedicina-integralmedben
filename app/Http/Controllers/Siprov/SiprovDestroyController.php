<?php
namespace App\Http\Controllers\Siprov;

use App\Http\Controllers\Controller;
use App\Models\Siprov;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovDestroyController extends Controller
{
    public function __invoke(Siprov $siprov): RedirectResponse
    {
        try {
            $siprov->delete();

            return to_route('siprov.index')
                ->with('message', 'Integração SIPROV removida com sucesso.')
                ->with('type', 'success');

        } catch (Throwable $e) {
            Log::error('Erro ao remover integração SIPROV.', [
                'siprov_id'         => $siprov->id,
                'codigo_integracao' => $siprov->codigo_integracao,
                'cpf_cnpj'          => $siprov->cpf_cnpj,
                'cod_plano'         => $siprov->cod_plano,
                'message'           => $e->getMessage(),
                'file'              => $e->getFile(),
                'line'              => $e->getLine(),
            ]);

            return back()
                ->with('message', 'Erro ao remover integração SIPROV.')
                ->with('type', 'error');
        }
    }
}