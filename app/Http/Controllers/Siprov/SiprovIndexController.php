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
    /**
     * Limite de segurança para não ficar preso em paginação infinita da SIPROV.
     */
    private const MAX_PAGINAS = 50;

    public function __construct(
        private readonly SiprovAssociadoService $siprovService,
        private readonly AssociadosTenantParcenteService $associadosTenantService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $situacaoBeneficio = $request->input('situacaoBeneficio') ?: SiprovAssociadoQueryData::TODAS_SITUACOES;

        try {
            $itens = $this->buscarTodasPaginas($situacaoBeneficio);

            return Inertia::render('Siprov/Index', [
                'associados' => $this->associadosTenantService->AssociadosTenant($itens),
                'siprovError' => null,
            ]);
        } catch (SiprovException $e) {
            return Inertia::render('Siprov/Index', [
                'associados' => null,
                'siprovError' => $e->getMessage(),
            ]);
        }
    }

    /**
     * A busca e os filtros da tela são locais, então carregamos todas as páginas
     * da SIPROV e a paginação é feita no front.
     */
    private function buscarTodasPaginas(string $situacaoBeneficio): array
    {
        $itens = [];
        $pagina = 1;

        do {
            $response = $this->siprovService->query(
                SiprovAssociadoQueryData::fromRequest($situacaoBeneficio, $pagina > 1 ? $pagina : null)
            );

            array_push($itens, ...($response['itens'] ?? []));
            $pagina++;
        } while (($response['proximaPagina'] ?? false) && $pagina <= self::MAX_PAGINAS);

        return $itens;
    }
}
