<?php

namespace App\Services\Tenant;

use App\Models\Audit;
use App\Models\Desempenho;
use App\Models\TenantPlano;
use App\Models\User;
use App\Support\Formatar;
use App\Support\Planos;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Progresso das metas de desempenho. A função "registro de beneficiário por
 * plano" conta os registros auditados (registro_plano) feitos pelos usuários
 * selecionados na meta, no período e no(s) plano(s) escolhido(s).
 */
class DesempenhoService
{
    public const STATUS = [
        'nao_iniciada' => 'Não iniciada',
        'em_andamento' => 'Em andamento',
        'atingida' => 'Atingida',
        'encerrada' => 'Encerrada',
    ];

    /**
     * Planos para o formulário: os habilitados no tenant (ou todos, se nenhum).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function planos(string $tenantId): array
    {
        $habilitados = TenantPlano::where('tenant_id', $tenantId)->pluck('cod_plano')->map(fn ($c) => (string) $c);
        $opcoes = collect(Planos::options());

        return ($habilitados->isEmpty() ? $opcoes : $opcoes->whereIn('value', $habilitados))->values()->all();
    }

    public function planoLabel(?string $codPlano): ?string
    {
        if (! $codPlano) {
            return null;
        }

        return collect(Planos::options())->firstWhere('value', $codPlano)['label'] ?? "Plano {$codPlano}";
    }

    /**
     * Progresso da meta: por usuário e consolidado conforme o tipo (individual/coletiva).
     */
    public function progresso(Desempenho $desempenho, string $tenantId): array
    {
        $usuarios = $this->usuarios($desempenho);

        if ($desempenho->ehComissao()) {
            return $this->progressoComissao($desempenho, $tenantId, $usuarios);
        }

        $registros = $this->registros($desempenho, $tenantId, $usuarios->pluck('id')->all());
        $contagem = $registros->countBy('user_id');
        $meta = max(1, $desempenho->meta);
        $individual = $desempenho->tipo_meta === Desempenho::TIPO_INDIVIDUAL;

        $porUsuario = $usuarios
            ->map(fn (User $user) => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'total' => $total = $contagem[$user->id] ?? 0,
                'valor' => 0.0,
                'percentual' => $individual ? min(100, (int) round($total / $meta * 100)) : null,
                'percentual_valor' => null,
                'atingiu' => $individual ? $total >= $desempenho->meta : null,
                'atingiu_valor' => null,
            ])
            ->sortByDesc('total')
            ->values();

        $total = $porUsuario->sum('total');
        $atingiram = $individual ? $porUsuario->where('atingiu', true)->count() : null;

        $atingida = $individual
            ? $porUsuario->isNotEmpty() && $atingiram === $porUsuario->count()
            : $total >= $desempenho->meta;

        return [
            'total' => $total,
            'valor' => 0.0,
            'meta_valor' => null,
            'usuarios' => $porUsuario->all(),
            'participantes' => $porUsuario->count(),
            'atingiram' => $atingiram,
            'atingiram_valor' => null,
            // Coletiva: total do grupo sobre a meta. Individual: fração de usuários que bateram.
            'percentual' => $individual
                ? ($porUsuario->isEmpty() ? 0 : (int) round($atingiram / $porUsuario->count() * 100))
                : min(100, (int) round($total / $meta * 100)),
            'percentual_valor' => null,
            'status' => $this->status($desempenho, $atingida),
            'por_plano' => $this->porPlano($desempenho, $tenantId, $registros),
        ];
    }

    /**
     * Progresso da função "comissão por venda de plano": quantidade de vendas e
     * R$ de comissão por usuário/plano, no período e no(s) plano(s) da meta.
     * O vendedor vem de new_values.vendedor_id (senão, o usuário da auditoria).
     */
    private function progressoComissao(Desempenho $desempenho, string $tenantId, Collection $usuarios): array
    {
        $registros = $this->registrosBase($desempenho, $tenantId);
        $participantes = $usuarios->keyBy('id');
        $planos = TenantPlano::where('tenant_id', $tenantId)->get()->keyBy('cod_plano');

        $totais = [];
        $valores = [];
        $porPlano = [];

        foreach ($registros as $audit) {
            $dados = $audit->new_values ?? [];
            $vendedorId = (int) ($dados['vendedor_id'] ?? $audit->user_id ?? 0);

            if (! $participantes->has($vendedorId)) {
                continue;
            }

            $comissao = $this->comissaoDoRegistro($dados, $planos);
            $codPlano = (string) ($dados['cod_plano'] ?? '');

            $totais[$vendedorId] = ($totais[$vendedorId] ?? 0) + 1;
            $valores[$vendedorId] = ($valores[$vendedorId] ?? 0) + $comissao;

            $porPlano[$codPlano] ??= [
                'cod_plano' => $codPlano,
                'plano' => $dados['plano'] ?? $this->planoLabel($codPlano),
                'total' => 0,
                'valor' => 0.0,
            ];
            $porPlano[$codPlano]['total']++;
            $porPlano[$codPlano]['valor'] = round($porPlano[$codPlano]['valor'] + $comissao, 2);
        }

        ksort($porPlano);

        $meta = max(1, $desempenho->meta);
        $metaValor = $desempenho->meta_valor !== null ? (float) $desempenho->meta_valor : null;
        $individual = $desempenho->tipo_meta === Desempenho::TIPO_INDIVIDUAL;

        $porUsuario = $usuarios
            ->map(fn (User $user) => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'total' => $total = $totais[$user->id] ?? 0,
                'valor' => $valor = round($valores[$user->id] ?? 0, 2),
                'percentual' => $individual ? min(100, (int) round($total / $meta * 100)) : null,
                'percentual_valor' => $individual && $metaValor ? min(100, (int) round($valor / $metaValor * 100)) : null,
                'atingiu' => $individual ? $total >= $desempenho->meta : null,
                'atingiu_valor' => $individual && $metaValor ? $valor >= $metaValor : null,
            ])
            ->sortByDesc('valor')
            ->values();

        $total = $porUsuario->sum('total');
        $valor = round($porUsuario->sum('valor'), 2);
        $atingiram = $individual ? $porUsuario->where('atingiu', true)->count() : null;
        $atingiramValor = $individual && $metaValor ? $porUsuario->where('atingiu_valor', true)->count() : null;

        $atingida = $individual
            ? $porUsuario->isNotEmpty()
                && $atingiram === $porUsuario->count()
                && ($metaValor === null || $atingiramValor === $porUsuario->count())
            : $total >= $desempenho->meta && ($metaValor === null || $valor >= $metaValor);

        return [
            'total' => $total,
            'valor' => $valor,
            'meta_valor' => $metaValor,
            'usuarios' => $porUsuario->all(),
            'participantes' => $porUsuario->count(),
            'atingiram' => $atingiram,
            'atingiram_valor' => $atingiramValor,
            'percentual' => $individual
                ? ($porUsuario->isEmpty() ? 0 : (int) round($atingiram / $porUsuario->count() * 100))
                : min(100, (int) round($total / $meta * 100)),
            'percentual_valor' => $metaValor === null ? null : ($individual
                ? ($porUsuario->isEmpty() ? 0 : (int) round($atingiramValor / $porUsuario->count() * 100))
                : min(100, (int) round($valor / $metaValor * 100))),
            'status' => $this->status($desempenho, $atingida),
            'por_plano' => array_values($porPlano),
        ];
    }

    /**
     * Comissão da venda: usa o snapshot gravado na auditoria; registros antigos
     * (sem snapshot) caem na regra atual do plano.
     */
    private function comissaoDoRegistro(array $dados, Collection $planos): float
    {
        if (($dados['comissao'] ?? null) !== null) {
            return (float) $dados['comissao'];
        }

        $plano = $planos->get((string) ($dados['cod_plano'] ?? ''));

        return $plano ? $plano->comissaoVenda() : 0.0;
    }

    /**
     * Registros por plano (cards do Show). "Todos os planos": um item por plano do
     * tenant; "apenas um plano": só o plano da meta, já que os outros não contam.
     *
     * @return array<int, array{cod_plano: string, plano: string, total: int}>
     */
    private function porPlano(Desempenho $desempenho, string $tenantId, Collection $registros): array
    {
        $totais = $registros->countBy(fn (Audit $audit) => (string) ($audit->new_values['cod_plano'] ?? ''));

        $planos = $desempenho->escopo_plano === Desempenho::ESCOPO_PLANO
            ? [['value' => (string) $desempenho->cod_plano, 'label' => $this->planoLabel($desempenho->cod_plano)]]
            : $this->planos($tenantId);

        return collect($planos)
            ->map(fn (array $plano) => [
                'cod_plano' => (string) $plano['value'],
                'plano' => $plano['label'],
                'total' => $totais[(string) $plano['value']] ?? 0,
            ])
            ->values()
            ->all();
    }

    private function status(Desempenho $desempenho, bool $atingida): string
    {
        $hoje = Carbon::today(Formatar::FUSO)->toDateString();

        return match (true) {
            $atingida => 'atingida',
            $hoje < $desempenho->data_inicio->toDateString() => 'nao_iniciada',
            $hoje > $desempenho->prazo->toDateString() => 'encerrada',
            default => 'em_andamento',
        };
    }

    /**
     * @return Collection<int, User>
     */
    private function usuarios(Desempenho $desempenho): Collection
    {
        return $desempenho->usuarios()->orderBy('users.name')->get(['users.id', 'users.name', 'users.email']);
    }

    /**
     * Registros dos participantes no período e no(s) plano(s) da meta
     * (auditoria no banco central, tag do tenant).
     *
     * @return Collection<int, Audit>
     */
    private function registros(Desempenho $desempenho, string $tenantId, array $userIds): Collection
    {
        if (! $userIds) {
            return collect();
        }

        return $this->registrosBase($desempenho, $tenantId)
            ->whereIn('user_id', $userIds)
            ->values();
    }

    /**
     * Registros no período e no(s) plano(s) da meta, sem filtrar por usuário.
     *
     * @return Collection<int, Audit>
     */
    private function registrosBase(Desempenho $desempenho, string $tenantId): Collection
    {
        $plano = $desempenho->escopo_plano === Desempenho::ESCOPO_PLANO ? (string) $desempenho->cod_plano : null;

        return Audit::where('event', 'registro_plano')
            ->where('tags', 'tenant:'.$tenantId)
            ->where('user_type', User::class)
            // Dias inteiros no horário de Brasília (o banco grava em UTC).
            ->whereBetween('created_at', [
                Carbon::parse($desempenho->data_inicio->toDateString(), Formatar::FUSO)->startOfDay()->utc(),
                Carbon::parse($desempenho->prazo->toDateString(), Formatar::FUSO)->endOfDay()->utc(),
            ])
            ->get(['user_id', 'new_values'])
            // cod_plano está em new_values (texto): filtrado em PHP.
            ->filter(fn (Audit $audit) => $plano === null || (string) ($audit->new_values['cod_plano'] ?? '') === $plano)
            ->values();
    }
}
