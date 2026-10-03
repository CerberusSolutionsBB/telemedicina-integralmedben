<?php

namespace App\Support;

use App\Models\TenantsDetail;

/**
 * Ações do CRUD de beneficiários (Patient) habilitadas por parceiro, na aba
 * Beneficiário da página do parceiro. Gravadas em tenants_details.configuracao.
 */
class BeneficiarioPermissoes
{
    public const CHAVE = 'beneficiario_permissoes';

    /** Ação => rótulo. */
    public const ACOES = [
        'create' => 'Cadastrar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'status' => 'Alterar status',
    ];

    /**
     * Sem configuração salva, só o cadastro vem habilitado.
     *
     * @return array<string, bool>
     */
    public static function padrao(): array
    {
        return ['create' => true, 'edit' => false, 'delete' => false, 'status' => false];
    }

    /**
     * @return array<string, bool>
     */
    public static function doTenant(?string $tenantId): array
    {
        $salvas = $tenantId
            ? (TenantsDetail::where('tenant_id', $tenantId)->first()?->configuracao[self::CHAVE] ?? [])
            : [];

        return collect(self::padrao())
            ->map(fn (bool $padrao, string $acao) => (bool) ($salvas[$acao] ?? $padrao))
            ->all();
    }

    public static function permite(?string $tenantId, string $acao): bool
    {
        return self::doTenant($tenantId)[$acao] ?? false;
    }
}
