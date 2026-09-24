<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovDependenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiprovSituacaoDependenteController extends Controller
{
    public function __construct(
        private readonly SiprovDependenteService $dependenteService,
    ) {}

    public function __invoke(Request $request, int $codBeneficio, int $codDependente): JsonResponse
    {
        $validated = $request->validate([
            'ativo' => ['required', 'boolean'],
        ]);

        $ativo = (bool) $validated['ativo'];
        $acao = $ativo ? 'ativar' : 'inativar';

        try {
            $this->dependenteService->alterarSituacao($codBeneficio, $codDependente, $ativo);

            return response()->json([
                'message' => $ativo ? 'Dependente ativado com sucesso.' : 'Dependente inativado com sucesso.',
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => "Erro ao {$acao} dependente: ".$e->getMessage(),
            ], 422);
        }
    }
}
