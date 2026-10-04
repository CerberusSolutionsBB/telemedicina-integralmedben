<?php

namespace App\Services\Tenant;

use App\Models\ExternalApiLog;
use App\Models\PacienteVinculoFamiliar;
use App\Models\Patient;
use App\Services\Siprov\SiprovDependenteService;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envia os membros da família do beneficiário (plano Clínica Familiar) como
 * dependentes do benefício do titular na SIPROV: cria os novos, atualiza os já
 * enviados (pelo codDependente salvo) e inativa os que saíram da lista.
 */
class PacienteDependentesSiprovService
{
    /** Tipo local (tipos_vinculo_familiar) => parentesco aceito pelo POST da SIPROV. */
    private const PARENTESCO = [
        'CONJUGE' => 'CONJUGE',
        'FILHO' => 'FILHO',
        'FILHA' => 'FILHO',
        'MAE' => 'PAI',
        'PAI' => 'PAI',
        'IRMAO' => 'IRMAO',
        'IRMA' => 'IRMAO',
        'AVO' => 'AVO',
        'NETO' => 'NETO',
        'ENTEADO' => 'ENTEADO',
        'SOGRO' => 'SOGRO',
        'OUTRO' => 'OUTRO',
    ];

    /** Sexo deduzido do tipo quando ele é inequívoco (o familiar não tem campo de sexo). */
    private const SEXO = [
        'FILHO' => 'Masculino',
        'PAI' => 'Masculino',
        'IRMAO' => 'Masculino',
        'FILHA' => 'Feminino',
        'MAE' => 'Feminino',
        'IRMA' => 'Feminino',
    ];

    public function __construct(
        private readonly PacientePlanoService $pacientePlanoService,
        private readonly SiprovDependenteService $siprovDependenteService,
    ) {}

    /**
     * @param  array<int, int>  $removidos  codDependente dos familiares tirados da lista
     * @return string|null  erros (um por dependente) ou null quando tudo foi enviado
     */
    public function enviar(string $tenantId, Patient $patient, array $removidos = []): ?string
    {
        $codBeneficio = $this->pacientePlanoService->codBeneficioFamiliar($tenantId, $patient->cpf);

        // Sem benefício familiar na SIPROV (plano interno, falha no registro...): nada a enviar.
        if (! $codBeneficio) {
            return null;
        }

        $erros = [];

        foreach ($patient->familiares()->orderBy('id')->get() as $familiar) {
            $payload = $this->payload($codBeneficio, $familiar);

            try {
                $resposta = $this->siprovDependenteService->salvar($payload);

                if (! empty($resposta['codDependente'])) {
                    $familiar->update(['siprov_cod_dependente' => (int) $resposta['codDependente']]);
                }

                $this->log($tenantId, $patient, $payload, 'success', $resposta);
            } catch (Throwable $e) {
                $this->log($tenantId, $patient, $payload, 'failed', null, $e->getMessage());
                $erros[] = "{$familiar->nome}: {$e->getMessage()}";
            }
        }

        foreach ($removidos as $codDependente) {
            $payload = ['acao' => 'inativar', 'codBeneficio' => $codBeneficio, 'codDependente' => $codDependente];

            try {
                $resposta = $this->siprovDependenteService->alterarSituacao($codBeneficio, $codDependente, false);
                $this->log($tenantId, $patient, $payload, 'success', $resposta);
            } catch (Throwable $e) {
                $this->log($tenantId, $patient, $payload, 'failed', null, $e->getMessage());
                $erros[] = "dependente removido #{$codDependente}: {$e->getMessage()}";
            }
        }

        return $erros ? implode(' | ', $erros) : null;
    }

    private function payload(int $codBeneficio, PacienteVinculoFamiliar $familiar): array
    {
        return array_filter([
            'codBeneficio' => $codBeneficio,
            // Com o código salvo a SIPROV atualiza o dependente em vez de criar outro.
            'codDependente' => $familiar->siprov_cod_dependente,
            'nome' => Str::limit(trim($familiar->nome), 100, ''),
            'cpf' => $familiar->cpf ?: null,
            'dataNascimento' => $familiar->data_nascimento?->format('d/m/Y'),
            'parentesco' => self::PARENTESCO[$familiar->tipo] ?? 'OUTRO',
            'sexo' => self::SEXO[$familiar->tipo] ?? null,
            'planos' => [(int) $familiar->plano_id],
            'ativo' => true,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    private function log(string $tenantId, Patient $patient, array $payload, string $status, ?array $response = null, ?string $erro = null): void
    {
        ExternalApiLog::create([
            'api' => 'siprov_dependente',
            'tenant_id' => $tenantId,
            'patient_id' => $patient->id,
            'status' => $status,
            'payload' => $payload,
            'response' => $response,
            'error_message' => $erro,
        ]);
    }
}
