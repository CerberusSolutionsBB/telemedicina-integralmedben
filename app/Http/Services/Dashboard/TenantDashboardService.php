<?php

namespace App\Http\Services\Dashboard;

use App\Enums\PatientSexoEnum;
use App\Enums\SmsStatusEnum;
use App\Enums\StatusRegistroEnum;
use App\Models\Audit;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\User;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Planos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Dashboard do painel do parceiro: beneficiários (banco do tenant), planos
 * contratados e SMS (banco central) do tenant atual.
 */
class TenantDashboardService
{
    private const MESES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    private const FAIXAS_ETARIAS = [
        ['label' => 'Até 17', 'min' => 0, 'max' => 17],
        ['label' => '18–29', 'min' => 18, 'max' => 29],
        ['label' => '30–44', 'min' => 30, 'max' => 44],
        ['label' => '45–59', 'min' => 45, 'max' => 59],
        ['label' => '60+', 'min' => 60, 'max' => null],
    ];

    public function __construct(private TenantPlanoCotaService $planoCotaService) {}

    public function getData(Tenant $tenant, int $year, ?int $month = null): array
    {
        $currentMonth = now()->month;

        // Período dos blocos filtráveis: o ano todo ou um mês dele.
        $periodo = function (Builder $q) use ($year, $month) {
            $q->whereYear('created_at', $year);
            if ($month) {
                $q->whereMonth('created_at', $month);
            }
        };

        $mensal = Patient::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'currentYear' => $year,
            'currentMonth' => $currentMonth,
            'selectedMonth' => $month,
            'monthLabels' => self::MESES,
            'updatedAt' => now()->format('d/m/Y H:i'),

            'totalPatients' => Patient::count(),
            'activePatients' => Patient::where('status', true)->count(),
            'newThisMonth' => Patient::whereYear('created_at', now()->year)->whereMonth('created_at', $currentMonth)->count(),
            'monthlyGrowth' => collect(range(1, 12))->map(fn ($m) => (int) ($mensal[$m] ?? 0))->all(),

            'porOrigem' => $this->porOrigem($periodo),
            'porUsuario' => $this->porUsuario($periodo),
            'porSexo' => $this->porSexo($periodo),
            'porFaixaEtaria' => $this->porFaixaEtaria($periodo),
            'planos' => $this->planos($tenant),
            'comissoes' => $this->comissoes($tenant, $year, $month),
            'sms' => $this->sms($tenant, $year),
        ];
    }

    /**
     * Vendas de plano e comissões do período (auditoria registro_plano):
     * total, ticket médio, ranking por vendedor e comissão por plano.
     */
    private function comissoes(Tenant $tenant, int $year, ?int $month): array
    {
        $audits = Audit::where('event', 'registro_plano')
            ->where('tags', 'tenant:'.$tenant->id)
            ->whereYear('created_at', $year)
            ->when($month, fn ($q) => $q->whereMonth('created_at', $month))
            ->get(['user_id', 'new_values']);

        $planos = TenantPlano::where('tenant_id', $tenant->id)->get()->keyBy('cod_plano');
        $nomesPlanos = collect(Planos::options())->pluck('label', 'value');

        $vendedores = [];
        $porPlano = [];
        $total = 0;
        $valor = 0.0;

        foreach ($audits as $audit) {
            $dados = $audit->new_values ?? [];
            $vendedorId = (int) ($dados['vendedor_id'] ?? $audit->user_id ?? 0);
            $comissao = $this->comissaoDoRegistro($dados, $planos);
            $codPlano = (string) ($dados['cod_plano'] ?? '');

            $total++;
            $valor += $comissao;

            if ($vendedorId) {
                $vendedores[$vendedorId] ??= ['id' => $vendedorId, 'total' => 0, 'valor' => 0.0];
                $vendedores[$vendedorId]['total']++;
                $vendedores[$vendedorId]['valor'] += $comissao;
            }

            $porPlano[$codPlano] ??= [
                'cod_plano' => $codPlano,
                'plano' => $dados['plano'] ?? ($nomesPlanos[$codPlano] ?? "Plano {$codPlano}"),
                'total' => 0,
                'valor' => 0.0,
            ];
            $porPlano[$codPlano]['total']++;
            $porPlano[$codPlano]['valor'] += $comissao;
        }

        $nomes = $vendedores ? User::whereIn('id', array_keys($vendedores))->pluck('name', 'id') : collect();

        return [
            'total' => $total,
            'valor' => round($valor, 2),
            'ticket' => $total ? round($valor / $total, 2) : 0.0,
            'vendedores' => collect($vendedores)
                ->map(fn (array $v) => [
                    'id' => $v['id'],
                    'nome' => $nomes[$v['id']] ?? 'Usuário removido',
                    'total' => $v['total'],
                    'valor' => round($v['valor'], 2),
                ])
                ->sortByDesc('valor')
                ->values()
                ->all(),
            'por_plano' => collect($porPlano)
                ->map(fn (array $p) => [...$p, 'valor' => round($p['valor'], 2)])
                ->sortByDesc('valor')
                ->values()
                ->all(),
        ];
    }

    /**
     * Comissão do registro: snapshot da auditoria; sem snapshot, usa a regra atual
     * do plano.
     */
    private function comissaoDoRegistro(array $dados, Collection $planos): float
    {
        if (($dados['comissao'] ?? null) !== null) {
            return (float) $dados['comissao'];
        }

        $plano = $planos->get((string) ($dados['cod_plano'] ?? ''));

        return $plano ? $plano->comissaoVenda() : 0.0;
    }

    /** @return array<int, array{label: string, total: int}> */
    private function porOrigem(callable $periodo): array
    {
        $totais = Patient::where($periodo)
            ->selectRaw('status_registro, COUNT(*) as total')
            ->groupBy('status_registro')
            ->pluck('total', 'status_registro');

        return collect(StatusRegistroEnum::cases())
            ->map(fn (StatusRegistroEnum $origem) => [
                'label' => $origem->label(),
                'total' => (int) ($totais[$origem->value] ?? 0),
            ])
            ->push(['label' => 'Não informado', 'total' => (int) ($totais[''] ?? 0)])
            ->filter(fn ($o) => $o['total'] > 0)
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /** Usuários que mais cadastraram no período (top 5). */
    private function porUsuario(callable $periodo): array
    {
        $totais = Patient::where($periodo)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'user_id');

        $nomes = User::whereIn('id', $totais->keys())->pluck('name', 'id');

        return $totais
            ->map(fn ($total, $userId) => ['nome' => $nomes[$userId] ?? 'Usuário removido', 'total' => (int) $total])
            ->values()
            ->all();
    }

    private function porSexo(callable $periodo): array
    {
        $totais = Patient::where($periodo)
            ->selectRaw('sexo, COUNT(*) as total')
            ->groupBy('sexo')
            ->pluck('total', 'sexo');

        return collect(PatientSexoEnum::cases())
            ->map(fn (PatientSexoEnum $sexo) => ['label' => $sexo->label(), 'total' => (int) ($totais[$sexo->value] ?? 0)])
            ->push(['label' => 'Não informado', 'total' => (int) ($totais[''] ?? 0)])
            ->values()
            ->all();
    }

    private function porFaixaEtaria(callable $periodo): array
    {
        $idades = Patient::where($periodo)
            ->whereNotNull('data_nascimento')
            ->selectRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) as idade')
            ->pluck('idade');

        return collect(self::FAIXAS_ETARIAS)
            ->map(fn ($faixa) => [
                'label' => $faixa['label'],
                'total' => $idades->filter(fn ($idade) => $idade >= $faixa['min'] && ($faixa['max'] === null || $idade <= $faixa['max']))->count(),
            ])
            ->all();
    }

    /** Planos contratados com vagas, vidas em uso, valor e receita. */
    private function planos(Tenant $tenant): array
    {
        $nomes = collect(Planos::options())->pluck('label', 'value');
        $uso = $this->planoCotaService->uso($tenant->id);

        return TenantPlano::where('tenant_id', $tenant->id)
            ->get(['cod_plano', 'quantidade', 'saldo', 'valor'])
            ->map(function (TenantPlano $plano) use ($nomes, $uso) {
                $emUso = (int) ($uso[$plano->cod_plano] ?? 0);
                $valor = $plano->valor !== null ? (float) $plano->valor : null;

                return [
                    'cod_plano' => (string) $plano->cod_plano,
                    'plano' => $nomes[(string) $plano->cod_plano] ?? "Plano {$plano->cod_plano}",
                    'quantidade' => $plano->quantidade,
                    'disponivel' => max(0, $plano->saldo),
                    'em_uso' => $emUso,
                    'valor' => $valor,
                    'receita' => $emUso * ($valor ?? 0),
                ];
            })
            ->sortByDesc('em_uso')
            ->values()
            ->all();
    }

    private function sms(Tenant $tenant, int $year): array
    {
        $porStatus = SmsLogs::where('tenant_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'saldo' => (int) $tenant->sms_quota,
            'enviados' => (int) ($porStatus[SmsStatusEnum::Sent->value] ?? 0),
            'falhas' => (int) ($porStatus[SmsStatusEnum::Failed->value] ?? 0),
            'pendentes' => (int) ($porStatus[SmsStatusEnum::Pending->value] ?? 0),
        ];
    }
}
