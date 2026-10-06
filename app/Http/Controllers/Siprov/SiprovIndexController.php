<?php

namespace App\Http\Controllers\Siprov;

use App\Data\SiprovAssociadoQueryData;
use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\AssociadosTenantParcenteService;
use App\Services\Siprov\SiprovAssociadoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiprovIndexController extends Controller
{
    public function __construct(
        private readonly SiprovAssociadoService $siprovService,
        private readonly AssociadosTenantParcenteService $associadosTenantService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $situacaoBeneficio = $request->input('situacaoBeneficio') ?: SiprovAssociadoQueryData::TODAS_SITUACOES;

        try {
            // A busca e os filtros da tela são locais: carrega todas as páginas e pagina no front.
            $itens = $this->siprovService->todos($situacaoBeneficio);

            return Inertia::render('Siprov/Index', [
                'associados' => $this->associadosTenantService->AssociadosTenant($itens),
                'codPlanoFamiliar' => (string) config('siprov.planos.clinica_familiar'),
                'siprovError' => null,
            ]);
        } catch (SiprovException $e) {
            return Inertia::render('Siprov/Index', [
                'associados' => null,
                'siprovError' => $e->getMessage(),
            ]);
        }
    }
}
