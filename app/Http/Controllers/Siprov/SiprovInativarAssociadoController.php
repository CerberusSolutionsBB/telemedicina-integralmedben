<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovBeneficioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiprovInativarAssociadoController extends Controller
{
    public function __construct(
        private readonly SiprovBeneficioService $beneficioService,
    ) {}

    public function __invoke(Request $request, int $codBeneficio): JsonResponse
    {
        $validated = $request->validate([
            'cpf'      => ['required', 'string'],
            'codPlano' => ['required', 'integer'],
        ]);

        try {
            $this->beneficioService->inativar($codBeneficio, $validated['codPlano'], $validated['cpf']);

            return response()->json([
                'message' => 'Associado inativado com sucesso.',
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => 'Erro ao inativar associado: '.$e->getMessage(),
            ], 422);
        }
    }
}
