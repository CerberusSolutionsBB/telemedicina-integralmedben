<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovBeneficioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiprovSituacaoAssociadoController extends Controller
{
    public function __construct(
        private readonly SiprovBeneficioService $beneficioService,
    ) {}

    public function __invoke(Request $request, int $codBeneficio): JsonResponse
    {
        $validated = $request->validate([
            'cpf'      => ['required', 'string'],
            'codPlano' => ['required', 'integer'],
            'ativo'    => ['required', 'boolean'],
        ]);

        $ativo = (bool) $validated['ativo'];
        $acao = $ativo ? 'ativar' : 'inativar';

        try {
            $this->beneficioService->alterarSituacao($codBeneficio, $validated['codPlano'], $validated['cpf'], $ativo);

            return response()->json([
                'message' => $ativo ? 'Associado ativado com sucesso.' : 'Associado inativado com sucesso.',
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => "Erro ao {$acao} associado: ".$e->getMessage(),
            ], 422);
        }
    }
}
