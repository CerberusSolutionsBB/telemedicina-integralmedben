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
        $situacaoBeneficio = $request->input('situacaoBeneficio') ?: 'Ativo';
        $pagina = $request->input('pagina') ? (int) $request->input('pagina') : 1;
        $data = SiprovAssociadoQueryData::fromRequest($situacaoBeneficio, $pagina > 1 ? $pagina : null);

        try {
            $response = $this->siprovService->query($data);

            $associados = $this->associadosTenantService->AssociadosTenant(
                $response['itens'] ?? []
            );

            return Inertia::render('Siprov/Index', [
                'associados' => $associados,
                'siprovError' => null,
                'pagination' => [
                    'currentPage' => $response['paginaAtual'] ?? 1,
                    'hasNextPage' => $response['proximaPagina'] ?? false,
                    'total' => $response['quantidade'] ?? 0,
                ],
            ]);
        } catch (SiprovException $e) {
            return Inertia::render('Siprov/Index', [
                'associados' => null,
                'siprovError' => $e->getMessage(),
            ]);
        }
    }
}
