<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovAssociadoService;
use Illuminate\Http\JsonResponse;

class SiprovCancelBeneficioController extends Controller
{
    public function __construct(
        private readonly SiprovAssociadoService $siprovService,
    ) {}

    public function __invoke(int $codBeneficio): JsonResponse
    {
        try {
            $this->siprovService->cancelarBeneficio($codBeneficio);

            return response()->json([
                'message' => 'Benefício cancelado com sucesso.',
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => 'Erro ao cancelar benefício: '.$e->getMessage(),
            ], 422);
        }
    }
}
