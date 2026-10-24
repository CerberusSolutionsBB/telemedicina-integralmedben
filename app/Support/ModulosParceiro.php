<?php

namespace App\Support;

use App\Models\TenantsDetail;

/**
 * Módulos opcionais do painel do parceiro, ligados por parceiro na aba Configuração
 * da página do parceiro. Gravados em tenants_details.configuracao['modulos'].
 * Desligado por padrão: o admin libera para quem contratar.
 */
class ModulosParceiro
{
    public const CHAVE = 'modulos';

    /** Módulo => [rótulo, o que libera]. A chave é a usada no middleware ('modulo:servicos'). */
    public const MODULOS = [
        'servicos' => [
            'label' => 'Serviços',
            'descricao' => 'Cadastro dos serviços oferecidos pelo parceiro (ex.: teleconsulta, exames).',
        ],
        'convenios' => [
            'label' => 'Convênios',
            'descricao' => 'Cadastro dos convênios do parceiro (ex.: farmácias, óticas, laboratórios).',
        ],
        'cupons_beneficio' => [
            'label' => 'Cupons de benefício',
            'descricao' => 'Descontos que o beneficiário usa nos convênios e serviços. Precisa de Convênios ou Serviços ligado.',
        ],
        'processos' => [
            'label' => 'Processos',
            'descricao' => 'Cadastro dos processos do parceiro, com valor, campos personalizados e serviços vinculados.',
        ],
    ];

    /**
     * @return array<string, bool>
     */
    public static function doTenant(?string $tenantId): array
    {
        $salvos = $tenantId
            ? (TenantsDetail::where('tenant_id', $tenantId)->first()?->configuracao[self::CHAVE] ?? [])
            : [];

        return collect(self::MODULOS)->mapWithKeys(fn ($config, $modulo) => [$modulo => (bool) ($salvos[$modulo] ?? false)])->all();
    }

    public static function habilitado(?string $tenantId, string $modulo): bool
    {
        return self::doTenant($tenantId)[$modulo] ?? false;
    }

    public static function definir(string $tenantId, string $modulo, bool $habilitado): void
    {
        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenantId]);
        $configuracao = $detail->configuracao ?? [];
        $configuracao[self::CHAVE] = [...($configuracao[self::CHAVE] ?? []), $modulo => $habilitado];

        $detail->update(['configuracao' => $configuracao]);
    }

    /**
     * Módulos com rótulo e descrição para a aba Configuração.
     *
     * @return array<int, array{key: string, label: string, descricao: string, habilitado: bool}>
     */
    public static function paraTela(string $tenantId): array
    {
        $estado = self::doTenant($tenantId);

        return collect(self::MODULOS)
            ->map(fn ($config, $modulo) => ['key' => $modulo, ...$config, 'habilitado' => $estado[$modulo]])
            ->values()
            ->all();
    }
}
