<?php

namespace App\Services\Tenant;

use App\Models\Patient;
use App\Models\Tenant;

/**
 * CPFs dos pacientes (não excluídos) cadastrados no banco do parceiro: a mesma base
 * da tela Beneficiários. O espelho no central (central_patients) pode ter
 * pacientes que já foram excluídos do parceiro, por isso não serve para isso.
 */
class PacientesDoParceiroService
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $cache = [];

    public function ehPaciente(string $tenantId, ?string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', (string) $cpf);

        return $cpf !== '' && isset($this->cpfs($tenantId)[$cpf]);
    }

    /**
     * @return array<string, true> CPF (só dígitos) => true
     */
    private function cpfs(string $tenantId): array
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $tenant = Tenant::find($tenantId);

        $cpfs = $tenant
            ? $tenant->run(fn () => Patient::whereNotNull('cpf')->pluck('cpf')->all())
            : [];

        return $this->cache[$tenantId] = collect($cpfs)
            ->map(fn ($cpf) => preg_replace('/\D/', '', (string) $cpf))
            ->filter()
            ->mapWithKeys(fn (string $cpf) => [$cpf => true])
            ->all();
    }
}
