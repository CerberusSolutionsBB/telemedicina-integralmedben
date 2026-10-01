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
 * dos perfis da meta, no período e no(s) plano(s) escolhido(s).
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
                'percentual' => $individual ? min(100, (int) round($total / $meta * 100)) : null,
                'atingiu' => $individual ? $total >= $desempenho->meta : null,
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
            'usuarios' => $porUsuario->all(),
            'participantes' => $porUsuario->count(),
            'atingiram' => $atingiram,
            // Coletiva: total do grupo sobre a meta. Individual: fração de usuários que bateram.
            'percentual' => $individual
                ? ($porUsuario->isEmpty() ? 0 : (int) round($atingiram / $porUsuario->count() * 100))
                : min(100, (int) round($total / $meta * 100)),
            'status' => $this->status($desempenho, $atingida),
            'por_plano' => $this->porPlano($desempenho, $tenantId, $registros),
        ];
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
        $roles = $desempenho->roles->pluck('name')->all();

        return $roles ? User::role($roles)->orderBy('name')->get(['id', 'name', 'email']) : collect();
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

        $plano = $desempenho->escopo_plano === Desempenho::ESCOPO_PLANO ? (string) $desempenho->cod_plano : null;

        return Audit::where('event', 'registro_plano')
            ->where('tags', 'tenant:'.$tenantId)
            ->where('user_type', User::class)
            ->whereIn('user_id', $userIds)
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
