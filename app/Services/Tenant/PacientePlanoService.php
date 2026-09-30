<?php

namespace App\Services\Tenant;

use App\Http\Services\ExternalApi\SiprovExternalService;
use App\Models\ExternalApiLog;
use App\Models\Patient;
use App\Models\TelemedicinaTenant;
use App\Models\TenantPlano;
use App\Support\SiprovPlanos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Events\AuditCustom;
use Throwable;

/**
 * Plano escolhido no cadastro manual do paciente: integra o paciente na SIPROV
 * (associado + benefício) e o adiciona aos associados da telemedicina do tenant,
 * consumindo a cota do plano.
 */
class PacientePlanoService
{
    public function __construct(
        private readonly TenantPlanoCotaService $planoCotaService,
        private readonly SiprovExternalService $siprovService,
    ) {}

    /**
     * Planos habilitados para o tenant com as vagas disponíveis (saldo gravado).
     * Plano com saldo 0 vem como `esgotado` e não pode ser escolhido.
     *
     * @return array<int, array{value: string, label: string, quantidade: int, emUso: int, disponivel: int, esgotado: bool}>
     */
    public function opcoes(string $tenantId): array
    {
        $labels = collect(SiprovPlanos::options())->pluck('label', 'value');

        return TenantPlano::where('tenant_id', $tenantId)
            ->get(['cod_plano', 'quantidade', 'saldo'])
            ->map(function (TenantPlano $plano) use ($labels) {
                $disponivel = max(0, $plano->saldo);

                return [
                    'value' => (string) $plano->cod_plano,
                    'label' => $labels[$plano->cod_plano] ?? "Plano {$plano->cod_plano}",
                    'quantidade' => $plano->quantidade,
                    'emUso' => max(0, $plano->quantidade - $plano->saldo),
                    'disponivel' => $disponivel,
                    'esgotado' => $disponivel < 1,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Vínculo de telemedicina já existente para o CPF (manual ou via SIPROV).
     *
     * @return array{cod_plano: ?string, plano_label: string}|null
     */
    public function vinculoAtual(string $tenantId, ?string $cpf): ?array
    {
        $cpfLimpo = preg_replace('/\D/', '', (string) $cpf);

        if (! $cpfLimpo) {
            return null;
        }

        $vinculo = TelemedicinaTenant::where('tenant_id', $tenantId)
            ->whereIn('data->cpf_cnpj', array_unique([$cpfLimpo, (string) $cpf]))
            ->latest()
            ->first();

        if (! $vinculo) {
            return null;
        }

        $codigos = TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? []);
        $labels = collect(SiprovPlanos::options())->pluck('label', 'value');

        return [
            'cod_plano' => $codigos[0] ?? null,
            'plano_label' => $codigos
                ? collect($codigos)->map(fn ($c) => $labels[$c] ?? "Plano {$c}")->implode(', ')
                : ($vinculo->data['plano_label'] ?? 'Plano não identificado'),
        ];
    }

    /**
     * Valida o plano antes de salvar o paciente: CPF sem vínculo prévio, plano habilitado e com vaga.
     *
     * @throws ValidationException
     */
    public function validar(string $tenantId, string $codPlano, array $data): void
    {
        if ($atual = $this->vinculoAtual($tenantId, $data['cpf'] ?? null)) {
            throw ValidationException::withMessages([
                'cod_plano' => "Este CPF já está vinculado à telemedicina ({$atual['plano_label']}).",
            ]);
        }

        try {
            $this->planoCotaService->validarVinculos($tenantId, [[
                'nomePessoa' => $data['nome'] ?? 'Paciente',
                'planos' => [['codPlano' => $codPlano]],
            ]]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['cod_plano' => collect($e->errors())->flatten()->implode(' ')]);
        }
    }

    /**
     * Integra o paciente na SIPROV e o adiciona aos associados da telemedicina.
     * Retorna a mensagem de erro quando a SIPROV falha (o paciente continua salvo).
     */
    public function registrar(string $tenantId, Patient $patient, string $codPlano): ?string
    {
        $cpf = preg_replace('/\D/', '', (string) $patient->cpf);

        $payload = [
            'tenant_id' => $tenantId,
            'patient_id' => $patient->id,
            'nome' => $patient->nome,
            'cpf' => $cpf,
            'email' => $patient->email,
            'tel' => $patient->numero,
            'sexo' => $patient->sexo?->value ?? $patient->sexo,
            'birth_date' => $patient->data_nascimento?->format('Y-m-d'),
            'plan' => $codPlano,
        ];

        try {
            $result = $this->siprovService->registerPatient($payload);
        } catch (Throwable $e) {
            $this->log($tenantId, $patient, $payload, 'failed', errorMessage: $e->getMessage());

            Log::warning('Paciente | Falha ao registrar plano na SIPROV', [
                'tenant_id' => $tenantId,
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return $e->getMessage();
        }

        $this->log($tenantId, $patient, $payload, 'success', response: $result);

        try {
            $this->registrarVinculo($tenantId, $codPlano, (string) $patient->nome, $cpf, $result, $patient->id, 'cadastro_paciente');
        } catch (ValidationException $e) {
            // Saldo acabou entre a validação e o consumo (cadastros simultâneos).
            return collect($e->errors())->flatten()->implode(' ');
        }

        return null;
    }

    /**
     * Adiciona o associado (já registrado na SIPROV) aos associados de telemedicina
     * do tenant e consome a vaga do plano, juntos: sem saldo, nada é criado.
     *
     * @param  array  $result  retorno da integração SIPROV (associado/benefício)
     * @param  string  $origem  cadastro_paciente | formulario_publico
     *
     * @throws ValidationException quando o plano não tem saldo
     */
    public function registrarVinculo(string $tenantId, string $codPlano, string $nome, string $cpf, array $result, ?int $patientId, string $origem): TelemedicinaTenant
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $label = collect(SiprovPlanos::options())->pluck('label', 'value')[$codPlano] ?? '';

        $vinculo = DB::connection('mysql')->transaction(function () use ($tenantId, $codPlano, $nome, $cpf, $result, $patientId, $origem, $label) {
            $vinculo = TelemedicinaTenant::create([
                'tenant_id' => $tenantId,
                'data' => [
                    'siprov_id' => $result['associado']['codPessoa'] ?? 'USR-'.$cpf,
                    'title' => $nome,
                    'cpf_cnpj' => $cpf,
                    'cod_plano' => $codPlano,
                    'cod_planos' => [$codPlano],
                    'plano_label' => $label,
                    'codigo_integracao' => 'USR-'.$cpf,
                    'codBeneficio' => $result['beneficio']['codBeneficio'] ?? null,
                    'patient_id' => $patientId,
                    'origem' => $origem,
                ],
            ]);

            $this->planoCotaService->consumir($tenantId, [$codPlano], $patientId, $vinculo->id);

            return $vinculo;
        });

        $this->auditarRegistro($vinculo, $tenantId, $codPlano, $label, $nome, $patientId, $origem);

        return $vinculo;
    }

    /**
     * Auditoria do registro do paciente no plano, depois do consumo da vaga:
     * além de usuário, IP e dispositivo (resolvers), guarda o saldo de cada plano
     * do tenant e o total de pacientes por plano naquele momento.
     */
    private function auditarRegistro(TelemedicinaTenant $vinculo, string $tenantId, string $codPlano, string $label, string $nome, ?int $patientId, string $origem): void
    {
        $vinculo->auditEvent = 'registro_plano';
        $vinculo->isCustomEvent = true;
        $vinculo->auditCustomOld = [];
        $vinculo->auditCustomNew = [
            'tenant_id' => $tenantId,
            'paciente_id' => $patientId,
            'paciente' => $nome,
            // Nome no momento do registro: o model User é o mesmo no central e nos
            // tenants, então user_id sozinho não identifica a pessoa com segurança.
            'usuario' => auth()->user()?->name,
            'origem' => $origem,
            'cod_plano' => $codPlano,
            'plano' => $label,
            'planos' => $this->planoCotaService->resumo($tenantId),
        ];

        Event::dispatch(new AuditCustom($vinculo));

        $vinculo->isCustomEvent = false;
    }

    private function log(string $tenantId, Patient $patient, array $payload, string $status, ?array $response = null, ?string $errorMessage = null): void
    {
        ExternalApiLog::create([
            'api' => 'siprov',
            'tenant_id' => $tenantId,
            'patient_id' => $patient->id,
            'status' => $status,
            'payload' => $payload,
            'response' => $response,
            'error_message' => $errorMessage,
        ]);
    }
}
