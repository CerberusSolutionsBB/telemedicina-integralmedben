<?php

namespace App\Services\Tenant;

use App\Http\Services\ExternalApi\SiprovExternalService;
use App\Models\Audit;
use App\Models\ExternalApiLog;
use App\Models\Patient;
use App\Models\Siprov;
use App\Models\TelemedicinaTenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\User;
use App\Support\Formatar;
use App\Support\Planos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Contracts\Auditable;
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
     * @return array<int, array{value: string, label: string, quantidade: int, emUso: int, disponivel: int, esgotado: bool, siprov: bool, familiar: bool}>
     */
    public function opcoes(string $tenantId): array
    {
        $labels = collect(Planos::options())->pluck('label', 'value');

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
                    'siprov' => Planos::integraSiprov($plano->cod_plano),
                    'familiar' => Planos::familiar($plano->cod_plano),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Vínculo de telemedicina já existente para o CPF (manual ou via SIPROV).
     *
     * @return array{cod_plano: ?string, plano_label: string, familiar: bool}|null
     */
    public function vinculoAtual(string $tenantId, ?string $cpf): ?array
    {
        $vinculo = $this->vinculo($tenantId, $cpf);

        if (! $vinculo) {
            return null;
        }

        $codigos = $this->codigos($vinculo);
        $labels = collect(Planos::options())->pluck('label', 'value');

        return [
            'cod_plano' => $codigos[0] ?? null,
            'plano_label' => $codigos
                ? collect($codigos)->map(fn ($c) => $labels[$c] ?? "Plano {$c}")->implode(', ')
                : ($vinculo->data['plano_label'] ?? 'Plano não identificado'),
            'familiar' => collect($codigos)->contains(fn ($c) => Planos::familiar($c)),
        ];
    }

    /**
     * Nome do plano de vários CPFs de uma vez (listagem de beneficiários), com a
     * mesma prioridade do vinculoAtual(): plano interno, depois telemedicina.
     *
     * @param  array<int, ?string>  $cpfs
     * @return array<string, string> CPF (só dígitos) => nome do plano
     */
    public function planosPorCpf(string $tenantId, array $cpfs): array
    {
        $digitos = collect($cpfs)->map(fn ($cpf) => preg_replace('/\D/', '', (string) $cpf))->filter()->unique()->values();

        if ($digitos->isEmpty()) {
            return [];
        }

        $labels = collect(Planos::options())->pluck('label', 'value');
        $nome = fn (array $codigos, ?string $padrao = null) => $codigos
            ? collect($codigos)->map(fn ($c) => $labels[$c] ?? "Plano {$c}")->implode(', ')
            : ($padrao ?: 'Plano não identificado');

        $planos = [];

        // Telemedicina (SIPROV): CPF gravado com ou sem máscara; o mais recente vence.
        $formatos = $digitos->flatMap(fn ($c) => [$c, preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $c)])->all();

        TelemedicinaTenant::where('tenant_id', $tenantId)
            ->whereIn('data->cpf_cnpj', $formatos)
            ->orderBy('created_at')
            ->get()
            ->each(function (TelemedicinaTenant $vinculo) use (&$planos, $nome) {
                $cpf = preg_replace('/\D/', '', (string) ($vinculo->data['cpf_cnpj'] ?? ''));
                $planos[$cpf] = $nome(TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? []), $vinculo->data['plano_label'] ?? null);
            });

        // Plano interno tem prioridade (sobrescreve a telemedicina).
        TenantPlanoBeneficiario::where('tenant_id', $tenantId)
            ->whereIn('cpf', $digitos)
            ->orderBy('id')
            ->get(['cpf', 'cod_plano'])
            ->each(function (TenantPlanoBeneficiario $vinculo) use (&$planos, $nome) {
                $planos[$vinculo->cpf] = $nome([(string) $vinculo->cod_plano]);
            });

        // Sem vínculo no parceiro: usa o plano do associado registrado na SIPROV
        // (tela Telemedicina), casando pelo CPF.
        $faltantes = $digitos->reject(fn ($cpf) => isset($planos[$cpf]));

        if ($faltantes->isNotEmpty()) {
            $formatosFaltantes = $faltantes->flatMap(fn ($c) => [$c, preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $c)])->all();

            Siprov::whereIn('cpf_cnpj', $formatosFaltantes)
                ->orderBy('integrated_at')
                ->orderBy('id')
                ->get(['cpf_cnpj', 'cod_plano'])
                ->each(function (Siprov $siprov) use (&$planos, $nome) {
                    $cpf = preg_replace('/\D/', '', (string) $siprov->cpf_cnpj);
                    $codigo = (string) ($siprov->cod_plano ?? '');

                    if ($cpf === '' || $codigo === '') {
                        return;
                    }

                    $planos[$cpf] = $nome([$codigo]);
                });
        }

        return $planos;
    }

    /**
     * Detalhes para a tela do beneficiário: o plano (quem registrou, quando e por
     * onde) e o cadastro (quem criou e quando), a partir da auditoria.
     */
    public function detalhes(string $tenantId, Patient $patient): array
    {
        return [
            'plano' => $this->detalhePlano($tenantId, $patient),
            'cadastro' => $this->detalheCadastro($tenantId, $patient),
        ];
    }

    private function detalhePlano(string $tenantId, Patient $patient): ?array
    {
        $vinculo = $this->vinculo($tenantId, $patient->cpf);

        if (! $vinculo) {
            return null;
        }

        $atual = $this->vinculoAtual($tenantId, $patient->cpf);

        // registro_plano (cadastro manual/formulário público) tem usuário e origem;
        // vínculos pelo modal SIPROV só têm o "created" do vínculo.
        $audit = Audit::where('auditable_type', $vinculo::class)
            ->where('auditable_id', $vinculo->id)
            ->whereIn('event', ['registro_plano', 'created'])
            ->orderByRaw("event = 'registro_plano' desc")
            ->first();

        $origem = $audit?->new_values['origem'] ?? null;

        return [
            'plano' => $atual['plano_label'],
            'siprov' => $vinculo instanceof TelemedicinaTenant,
            'origem' => match ($origem) {
                'cadastro_paciente' => 'Cadastro manual',
                'formulario_publico' => 'Formulário público',
                default => $vinculo instanceof TelemedicinaTenant ? 'Vínculo pela SIPROV' : 'Cadastro',
            },
            // cadastro_paciente | formulario_publico | null (vínculo pela SIPROV)
            'origem_tipo' => $origem,
            'usuario' => $audit?->event === 'registro_plano' ? ($audit->new_values['usuario'] ?? null) : null,
            'data_hora' => Formatar::dataHora($audit?->created_at ?? $vinculo->created_at),
        ];
    }

    private function detalheCadastro(string $tenantId, Patient $patient): array
    {
        $base = fn () => Audit::where('auditable_type', Patient::class)
            ->where('auditable_id', $patient->id)
            ->where('event', 'created');

        // Auditorias anteriores à tag do tenant: aceita a sem tag gravada no mesmo
        // instante da criação (ids de paciente se repetem entre tenants).
        $audit = $base()->where('tags', 'tenant:'.$tenantId)->first()
            ?? ($patient->created_at
                ? $base()->whereNull('tags')
                    ->whereBetween('created_at', [$patient->created_at->copy()->subSeconds(5), $patient->created_at->copy()->addSeconds(5)])
                    ->first()
                : null);

        // Auditoria gravada no contexto do tenant: user_id é um usuário do tenant.
        $usuario = $audit?->user_id ? User::find($audit->user_id)?->name : null;

        return [
            'usuario' => $usuario,
            'data_hora' => Formatar::dataHora($audit?->created_at ?? $patient->created_at),
            'auditado' => (bool) $audit,
        ];
    }

    /**
     * Vínculo de plano do CPF: plano interno ou associado da telemedicina (SIPROV).
     */
    private function vinculo(string $tenantId, ?string $cpf): TenantPlanoBeneficiario|TelemedicinaTenant|null
    {
        $cpfLimpo = preg_replace('/\D/', '', (string) $cpf);

        if (! $cpfLimpo) {
            return null;
        }

        return TenantPlanoBeneficiario::where('tenant_id', $tenantId)->where('cpf', $cpfLimpo)->latest('id')->first()
            ?? TelemedicinaTenant::where('tenant_id', $tenantId)
                ->whereIn('data->cpf_cnpj', array_unique([$cpfLimpo, (string) $cpf]))
                ->latest()
                ->first();
    }

    /**
     * codBeneficio da SIPROV do titular quando o vínculo é do plano familiar
     * (é nele que os dependentes são gravados). Null sem vínculo SIPROV familiar.
     */
    public function codBeneficioFamiliar(string $tenantId, ?string $cpf): ?int
    {
        $vinculo = $this->vinculo($tenantId, $cpf);

        if (! $vinculo instanceof TelemedicinaTenant) {
            return null;
        }

        $familiar = collect($this->codigos($vinculo))->contains(fn ($codigo) => Planos::familiar($codigo));
        $codBeneficio = (int) ($vinculo->data['codBeneficio'] ?? 0);

        return $familiar && $codBeneficio > 0 ? $codBeneficio : null;
    }

    /**
     * @return array<int, string>
     */
    private function codigos(TenantPlanoBeneficiario|TelemedicinaTenant $vinculo): array
    {
        return $vinculo instanceof TenantPlanoBeneficiario
            ? [(string) $vinculo->cod_plano]
            : TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? []);
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

        // Plano interno: sem SIPROV; só vínculo próprio + consumo da vaga.
        if (! Planos::integraSiprov($codPlano)) {
            try {
                $this->registrarVinculoInterno($tenantId, $codPlano, (string) $patient->nome, $cpf, $patient->id, 'cadastro_paciente');
            } catch (ValidationException $e) {
                return collect($e->errors())->flatten()->implode(' ');
            }

            return null;
        }

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
        $label = collect(Planos::options())->pluck('label', 'value')[$codPlano] ?? '';

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
     * Vincula o beneficiário a um plano interno (sem SIPROV/telemedicina) e consome
     * a vaga, juntos: sem saldo, nada é criado. Auditado como os demais registros.
     *
     * @throws ValidationException quando o plano não tem saldo
     */
    public function registrarVinculoInterno(string $tenantId, string $codPlano, string $nome, string $cpf, ?int $patientId, string $origem): TenantPlanoBeneficiario
    {
        $cpf = preg_replace('/\D/', '', $cpf) ?: null;
        $label = collect(Planos::options())->pluck('label', 'value')[$codPlano] ?? '';

        $vinculo = DB::connection('mysql')->transaction(function () use ($tenantId, $codPlano, $nome, $cpf, $patientId) {
            $vinculo = TenantPlanoBeneficiario::create([
                'tenant_id' => $tenantId,
                'cod_plano' => $codPlano,
                'patient_id' => $patientId,
                'nome' => $nome,
                'cpf' => $cpf,
            ]);

            $this->planoCotaService->consumir($tenantId, [$codPlano], $patientId);

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
    private function auditarRegistro(Model&Auditable $vinculo, string $tenantId, string $codPlano, string $label, string $nome, ?int $patientId, string $origem): void
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
