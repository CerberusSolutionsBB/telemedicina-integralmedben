<?php

namespace App\Services\Tenant;

use App\Models\Audit;
use App\Models\User;
use App\Support\Formatar;
use App\Support\IconesLucide;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Cadastros com título, descrição, ícone e status (serviços, convênios): formato
 * das telas e histórico de alterações lido da auditoria (laravel-auditing).
 */
class CadastroIconeService
{
    private const EVENTOS = [
        'created' => 'Criado',
        'updated' => 'Alterado',
        'deleted' => 'Excluído',
        'restored' => 'Restaurado',
        // auditSync de uma relação (ex.: serviços do processo).
        'sync' => 'Vínculos alterados',
    ];

    private const CAMPOS = [
        'titulo' => 'Título',
        'descricao' => 'Descrição',
        'icone' => 'Ícone',
        'status' => 'Status',
    ];

    /**
     * @return array<string, mixed>
     */
    public function paraTela(Model $item): array
    {
        return [
            'id' => $item->id,
            'titulo' => $item->titulo,
            'descricao' => $item->descricao,
            'icone' => $item->icone,
            'icone_label' => IconesLucide::rotulo($item->icone),
            'status' => (bool) $item->status,
            'criado_em' => Formatar::dataHora($item->created_at),
            'atualizado_em' => Formatar::dataHora($item->updated_at),
        ];
    }

    /**
     * Histórico do item (mais recente primeiro), com quem fez e o que mudou.
     * $camposExtras (campo => rótulo) e $formatar (campo, valor) => ?string cobrem os
     * campos próprios de cada módulo; null em $formatar usa a formatação padrão.
     *
     * @param  array<string, string>  $camposExtras
     * @return array<int, array{id: int, evento: string, evento_key: string, usuario: ?string, data: ?string, alteracoes: array<int, array{campo: string, antes: ?string, depois: ?string}>}>
     */
    public function historico(Model $item, string $tenantId, array $camposExtras = [], ?Closure $formatar = null): array
    {
        $audits = Audit::where('auditable_type', $item::class)
            ->where('auditable_id', $item->getKey())
            ->where('tags', 'tenant:'.$tenantId)
            ->latest('created_at')
            ->latest('id')
            ->limit(100)
            ->get();

        // Auditoria gravada no contexto do tenant: user_id é um usuário do tenant.
        $usuarios = User::whereIn('id', $audits->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        return $audits->map(fn (Audit $audit) => [
            'id' => $audit->id,
            'evento' => self::EVENTOS[$audit->event] ?? $audit->event,
            'evento_key' => $audit->event,
            'usuario' => $usuarios[$audit->user_id] ?? null,
            'data' => Formatar::dataHora($audit->created_at),
            'alteracoes' => $this->alteracoes($audit, [...self::CAMPOS, ...$camposExtras], $formatar),
        ])->values()->all();
    }

    /**
     * @return array<int, array{campo: string, antes: ?string, depois: ?string}>
     */
    private function alteracoes(Audit $audit, array $campos, ?Closure $formatar): array
    {
        $antes = $audit->old_values ?? [];
        $depois = $audit->new_values ?? [];

        return collect($campos)
            ->filter(fn ($rotulo, $campo) => array_key_exists($campo, $antes) || array_key_exists($campo, $depois))
            ->map(fn ($rotulo, $campo) => [
                'campo' => $rotulo,
                'antes' => $this->valor($campo, $antes[$campo] ?? null, $formatar),
                'depois' => $this->valor($campo, $depois[$campo] ?? null, $formatar),
            ])
            ->values()
            ->all();
    }

    private function valor(string $campo, mixed $valor, ?Closure $formatar): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($formatar && ($formatado = $formatar($campo, $valor)) !== null) {
            return $formatado;
        }

        return match ($campo) {
            'status' => (bool) $valor ? 'Ativo' : 'Inativo',
            'icone' => IconesLucide::rotulo((string) $valor),
            default => (string) $valor,
        };
    }
}
