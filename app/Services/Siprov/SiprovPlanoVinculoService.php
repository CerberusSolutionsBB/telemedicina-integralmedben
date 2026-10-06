<?php

namespace App\Services\Siprov;

use App\Enums\QuestionRoleEnum;
use App\Models\CentralPatientAnswer;
use App\Models\Question;
use App\Models\TelemedicinaTenant;
use App\Models\TenantPlanoBeneficiario;
use App\Services\Tenant\TenantPlanoCotaService;

/**
 * Leva o plano dos associados da SIPROV para os parceiros (Página do Parceiro),
 * casando pelo CPF: completa o vínculo gravado sem plano e cria o vínculo do
 * paciente do parceiro que ficou sem nenhum (ex.: cadastro pelo formulário dinâmico).
 * Não consome vaga do plano: é correção de dados de quem já está na SIPROV.
 */
class SiprovPlanoVinculoService
{
    public const ACAO_PLANO_PREENCHIDO = 'Plano preenchido';

    public const ACAO_VINCULO_CRIADO = 'Vínculo criado';

    /**
     * @param  array<int, array>  $associados  itens da busca de associados da SIPROV
     * @param  bool  $simular  só lista o que seria alterado, sem gravar
     * @return array<int, array{acao: string, tenant_id: string, nome: string, cpf: string, plano: string}>
     */
    public function sincronizar(array $associados, bool $simular = false): array
    {
        $porCpf = $this->associadosComPlanoPorCpf($associados);

        if (! $porCpf) {
            return [];
        }

        return [
            ...$this->completarPlanos($porCpf, $simular),
            ...$this->criarVinculosFaltantes($porCpf, $simular),
        ];
    }

    /**
     * Vínculos do parceiro gravados sem plano recebem o plano da SIPROV.
     *
     * @param  array<string, array>  $porCpf
     */
    private function completarPlanos(array $porCpf, bool $simular): array
    {
        $alterados = [];

        TelemedicinaTenant::whereNotNull('data->siprov_id')
            ->orderBy('id')
            ->get()
            ->each(function (TelemedicinaTenant $vinculo) use ($porCpf, $simular, &$alterados) {
                $data = $vinculo->data ?? [];
                $cpf = preg_replace('/\D/', '', (string) ($data['cpf_cnpj'] ?? ''));
                $item = $porCpf[$cpf] ?? null;

                if (! $item || TenantPlanoCotaService::codigosDoVinculo($data)) {
                    return;
                }

                if (! $simular) {
                    $vinculo->update(['data' => [
                        ...$data,
                        ...$this->dadosDoPlano($item),
                        'codBeneficio' => ($data['codBeneficio'] ?? null) ?: ($item['codBeneficio'] ?? null),
                    ]]);
                }

                $alterados[] = $this->linha(self::ACAO_PLANO_PREENCHIDO, (string) $vinculo->tenant_id, $cpf, $item, $data['title'] ?? null);
            });

        return $alterados;
    }

    /**
     * Paciente do parceiro (mesmo CPF) sem nenhum vínculo de plano: cria o vínculo
     * com o plano da SIPROV, como faz o vínculo pela Página do Parceiro.
     *
     * @param  array<string, array>  $porCpf
     */
    private function criarVinculosFaltantes(array $porCpf, bool $simular): array
    {
        $cpfQuestion = Question::where('role', QuestionRoleEnum::Cpf)->first();

        if (! $cpfQuestion) {
            return [];
        }

        $pacientes = CentralPatientAnswer::where('question_id', $cpfQuestion->id)
            ->whereIn('answer', array_keys($porCpf))
            ->with('patient:id,tenant_id')
            ->get()
            ->filter(fn (CentralPatientAnswer $answer) => $answer->patient?->tenant_id)
            ->map(fn (CentralPatientAnswer $answer) => ['tenant_id' => (string) $answer->patient->tenant_id, 'cpf' => (string) $answer->answer])
            ->unique(fn (array $paciente) => $paciente['tenant_id'].'|'.$paciente['cpf']);

        if ($pacientes->isEmpty()) {
            return [];
        }

        $vinculados = $this->vinculadosPorTenantECpf($pacientes->pluck('tenant_id')->unique()->all());
        $criados = [];

        foreach ($pacientes as $paciente) {
            if (isset($vinculados[$paciente['tenant_id'].'|'.$paciente['cpf']])) {
                continue;
            }

            $item = $porCpf[$paciente['cpf']];

            if (! $simular) {
                TelemedicinaTenant::create([
                    'tenant_id' => $paciente['tenant_id'],
                    'data' => [
                        'siprov_id' => $item['codPessoa'] ?? null,
                        'title' => $item['nomePessoa'] ?? '',
                        'cpf_cnpj' => $paciente['cpf'],
                        ...$this->dadosDoPlano($item),
                        'codigo_integracao' => $item['codPessoa'] ?? null,
                        'codBeneficio' => $item['codBeneficio'] ?? null,
                    ],
                ]);
            }

            $criados[] = $this->linha(self::ACAO_VINCULO_CRIADO, $paciente['tenant_id'], $paciente['cpf'], $item);
        }

        return $criados;
    }

    /**
     * Chaves "tenant|cpf" (CPF só dígitos) que já têm vínculo: telemedicina ou plano interno.
     *
     * @param  array<int, string>  $tenantIds
     * @return array<string, true>
     */
    private function vinculadosPorTenantECpf(array $tenantIds): array
    {
        $vinculados = [];

        TelemedicinaTenant::whereIn('tenant_id', $tenantIds)
            ->whereNotNull('data->siprov_id')
            ->get(['tenant_id', 'data'])
            ->each(function (TelemedicinaTenant $vinculo) use (&$vinculados) {
                $vinculados[$vinculo->tenant_id.'|'.preg_replace('/\D/', '', (string) ($vinculo->data['cpf_cnpj'] ?? ''))] = true;
            });

        TenantPlanoBeneficiario::whereIn('tenant_id', $tenantIds)
            ->whereNotNull('cpf')
            ->get(['tenant_id', 'cpf'])
            ->each(function (TenantPlanoBeneficiario $vinculo) use (&$vinculados) {
                $vinculados[$vinculo->tenant_id.'|'.$vinculo->cpf] = true;
            });

        return $vinculados;
    }

    /**
     * @return array{cod_plano: mixed, cod_planos: array<int, string>, plano_label: string}
     */
    private function dadosDoPlano(array $item): array
    {
        return [
            'cod_plano' => $item['planos'][0]['codPlano'],
            'cod_planos' => TenantPlanoCotaService::codigosDoItemSiprov($item),
            'plano_label' => $item['planos'][0]['nome'] ?? '',
        ];
    }

    private function linha(string $acao, string $tenantId, string $cpf, array $item, ?string $nome = null): array
    {
        return [
            'acao' => $acao,
            'tenant_id' => $tenantId,
            'nome' => (string) ($nome ?: ($item['nomePessoa'] ?? '')),
            'cpf' => $cpf,
            'plano' => collect($item['planos'])->pluck('nome')->filter()->implode(', '),
        ];
    }

    /**
     * Associados da SIPROV que têm plano, por CPF (só dígitos); o primeiro com plano vence.
     *
     * @return array<string, array>
     */
    private function associadosComPlanoPorCpf(array $associados): array
    {
        $porCpf = [];

        foreach ($associados as $item) {
            $cpf = preg_replace('/\D/', '', (string) ($item['cpfCnpj'] ?? ''));

            if ($cpf === '' || isset($porCpf[$cpf]) || ! TenantPlanoCotaService::codigosDoItemSiprov($item)) {
                continue;
            }

            // Só os planos com código, para o primeiro ser sempre válido.
            $item['planos'] = array_values(array_filter($item['planos'], fn ($plano) => ($plano['codPlano'] ?? '') !== ''));
            $porCpf[$cpf] = $item;
        }

        return $porCpf;
    }
}
