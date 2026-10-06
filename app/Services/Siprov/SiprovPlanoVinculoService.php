<?php

namespace App\Services\Siprov;

use App\Models\TelemedicinaTenant;
use App\Services\Tenant\TenantPlanoCotaService;

/**
 * Completa o plano dos associados vinculados ao parceiro (Página do Parceiro)
 * que foram gravados sem plano, com o plano do associado de mesmo CPF na SIPROV.
 */
class SiprovPlanoVinculoService
{
    /**
     * @param  array<int, array>  $associados  itens da busca de associados da SIPROV
     * @param  bool  $simular  só lista o que seria atualizado, sem gravar
     * @return array<int, array{vinculo_id: int, tenant_id: string, nome: string, cpf: string, plano: string}>
     */
    public function completarPlanos(array $associados, bool $simular = false): array
    {
        $porCpf = $this->associadosComPlanoPorCpf($associados);

        if (! $porCpf) {
            return [];
        }

        $atualizados = [];

        TelemedicinaTenant::whereNotNull('data->siprov_id')
            ->orderBy('id')
            ->get()
            ->each(function (TelemedicinaTenant $vinculo) use ($porCpf, $simular, &$atualizados) {
                $data = $vinculo->data ?? [];
                $cpf = preg_replace('/\D/', '', (string) ($data['cpf_cnpj'] ?? ''));
                $item = $porCpf[$cpf] ?? null;

                if (! $item || TenantPlanoCotaService::codigosDoVinculo($data)) {
                    return;
                }

                $primeiroPlano = $item['planos'][0];

                if (! $simular) {
                    $vinculo->update(['data' => [
                        ...$data,
                        'cod_plano' => $primeiroPlano['codPlano'],
                        'cod_planos' => TenantPlanoCotaService::codigosDoItemSiprov($item),
                        'plano_label' => $primeiroPlano['nome'] ?? '',
                        'codBeneficio' => ($data['codBeneficio'] ?? null) ?: ($item['codBeneficio'] ?? null),
                    ]]);
                }

                $atualizados[] = [
                    'vinculo_id' => $vinculo->id,
                    'tenant_id' => (string) $vinculo->tenant_id,
                    'nome' => (string) ($data['title'] ?? $item['nomePessoa'] ?? ''),
                    'cpf' => $cpf,
                    'plano' => collect($item['planos'])->pluck('nome')->filter()->implode(', '),
                ];
            });

        return $atualizados;
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
