<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovDependenteService;
use Illuminate\Http\JsonResponse;

class SiprovDependentesController extends Controller
{
    public function __construct(
        private readonly SiprovDependenteService $dependenteService,
    ) {}

    public function __invoke(int $codBeneficio): JsonResponse
    {
        try {
            return response()->json([
                'dependentes' => $this->dependenteService->listar($codBeneficio),
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => 'Erro ao listar dependentes: '.$e->getMessage(),
            ], 422);
        }
    }
}
