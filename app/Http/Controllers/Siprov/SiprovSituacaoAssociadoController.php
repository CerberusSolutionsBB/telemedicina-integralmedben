<?php

namespace App\Http\Controllers\Siprov;

use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovBeneficioService;
use App\Services\Siprov\SiprovStatusPacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiprovSituacaoAssociadoController extends Controller
{
    public function __construct(
        private readonly SiprovBeneficioService $beneficioService,
        private readonly SiprovStatusPacienteService $statusPacienteService,
    ) {}

    public function __invoke(Request $request, int $codBeneficio): JsonResponse
    {
        $validated = $request->validate([
            'cpf' => ['required', 'string'],
            'codPlano' => ['required', 'integer'],
            'ativo' => ['required', 'boolean'],
        ]);

        $ativo = (bool) $validated['ativo'];
        $acao = $ativo ? 'ativar' : 'inativar';

        try {
            $this->beneficioService->alterarSituacao($codBeneficio, $validated['codPlano'], $validated['cpf'], $ativo);

            // Mesmo status no beneficiário dos parceiros onde o associado existe.
            $pacientes = $this->statusPacienteService->sincronizar($validated['cpf'], $ativo);

            $mensagem = $ativo ? 'Associado ativado com sucesso.' : 'Associado inativado com sucesso.';

            if ($pacientes) {
                $mensagem .= ' '.($ativo ? 'Ativado' : 'Inativado')." também o beneficiário em {$pacientes} parceiro(s).";
            }

            return response()->json(['message' => $mensagem]);
        } catch (SiprovException $e) {
            return response()->json([
                'message' => "Erro ao {$acao} associado: ".$e->getMessage(),
            ], 422);
        }
    }
}
