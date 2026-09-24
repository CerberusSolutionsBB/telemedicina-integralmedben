<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovDependenteService;
use Illuminate\Http\JsonResponse;

class SiprovInativarDependenteController extends Controller
{
    public function __construct(
        private readonly SiprovDependenteService $dependenteService,
    ) {}

    public function __invoke(int $codBeneficio, int $codDependente): JsonResponse
    {
        try {
            $this->dependenteService->inativar($codBeneficio, $codDependente);

            return response()->json([
                'message' => 'Dependente inativado com sucesso.',
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => 'Erro ao inativar dependente: '.$e->getMessage(),
            ], 422);
        }
    }
}
