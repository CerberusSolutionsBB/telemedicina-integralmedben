<?php

namespace App\Services\Tenant;

use App\Models\Audit;
use App\Models\User;
use App\Support\Formatar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Relatório de produção por usuário no período: registros de beneficiários em
 * planos (auditoria registro_plano, a mesma contagem das metas), por usuário,
 * plano, origem e dia/semana, com a lista detalhada.
 */
class ProducaoUsuariosService
{
    public const ORIGENS = [
        'cadastro_paciente' => 'Cadastro manual',
        'formulario_publico' => 'Formulário público',
    ];

    /** Período máximo de um relatório (dias). */
    public const MAX_DIAS = 366;

    /** Até quantos dias a evolução é por dia; acima disso, por semana. */
    private const DIAS_EVOLUCAO_DIARIA = 31;

    private const SEM_USUARIO = 'Formulário público (sem login)';

    public function __construct(
        private readonly DesempenhoService $desempenhoService,
    ) {}

    /**
     * Opções dos filtros da tela.
     */
    public function opcoes(string $tenantId): array
    {
        return [
            'usuarios' => User::orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u) => ['value' => (string) $u->id, 'label' => $u->name])->all(),
            'perfis' => Role::orderBy('name')->pluck('name')
                ->map(fn ($nome) => ['value' => $nome, 'label' => $nome])->all(),
            'planos' => $this->desempenhoService->planos($tenantId),
            'origens' => collect(self::ORIGENS)->map(fn ($label, $value) => compact('value', 'label'))->values()->all(),
            'padrao' => $this->periodoPadrao(),
            'maxDias' => self::MAX_DIAS,
        ];
    }

    /**
     * @return array{de: string, ate: string} mês atual até hoje
     */
    public function periodoPadrao(): array
    {
        $hoje = now(Formatar::FUSO);

        return ['de' => $hoje->copy()->startOfMonth()->toDateString(), 'ate' => $hoje->toDateString()];
    }

    /**
     * @param  array{de?: ?string, ate?: ?string, usuario?: ?string, perfil?: ?string, plano?: ?string, origem?: ?string}  $filtros
     */
    public function dados(string $tenantId, array $filtros): array
    {
        $padrao = $this->periodoPadrao();
        $de = Carbon::parse($filtros['de'] ?? $padrao['de'], Formatar::FUSO)->startOfDay();
        $ate = Carbon::parse($filtros['ate'] ?? $padrao['ate'], Formatar::FUSO)->endOfDay();

        $registros = $this->registros($tenantId, $de, $ate, $filtros);
        $total = $registros->count();
        $percentual = fn (int $n) => $total ? round($n * 100 / $total, 1) : 0;

        $planos = $this->desempenhoService->planos($tenantId);

        return [
            'periodo' => ['de' => $de->format('d/m/Y'), 'ate' => $ate->format('d/m/Y')],
            'filtros' => $this->descreverFiltros($filtros),
            'total' => $total,
            'usuarios' => $this->porUsuario($registros, $planos, $percentual),
            'planos' => collect($planos)
                ->map(fn ($p) => ['label' => $p['label'], 'total' => $t = $registros->where('cod_plano', (string) $p['value'])->count(), 'percentual' => $percentual($t)])
                ->all(),
            'origens' => collect(self::ORIGENS)
                ->map(fn ($label, $key) => ['label' => $label, 'total' => $t = $registros->where('origem_key', $key)->count(), 'percentual' => $percentual($t)])
                ->values()
                ->all(),
            'evolucao' => $this->evolucao($registros, $de, $ate),
            'registros' => $registros->sortByDesc('data_ordem')->values()->all(),
            'planosColunas' => array_column($planos, 'label', 'value'),
            'gerado_por' => auth()->user()?->name,
            'gerado_em' => Formatar::dataHora(now()),
        ];
    }

    /**
     * @return Collection<int, array>
     */
    private function registros(string $tenantId, Carbon $de, Carbon $ate, array $filtros): Collection
    {
        $usuario = (string) ($filtros['usuario'] ?? '');
        $perfil = (string) ($filtros['perfil'] ?? '');
        $plano = (string) ($filtros['plano'] ?? '');
        $origem = (string) ($filtros['origem'] ?? '');

        $idsDoPerfil = $perfil !== '' ? User::role($perfil)->pluck('id')->all() : null;

        $audits = Audit::where('event', 'registro_plano')
            ->where('tags', 'tenant:'.$tenantId)
            // Dias inteiros no horário de Brasília (o banco grava em UTC).
            ->whereBetween('created_at', [$de->copy()->utc(), $ate->copy()->utc()])
            ->when($usuario !== '', fn ($q) => $q->where('user_type', User::class)->where('user_id', $usuario))
            ->when($idsDoPerfil !== null, fn ($q) => $q->where('user_type', User::class)->whereIn('user_id', $idsDoPerfil))
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'user_type', 'new_values', 'created_at'])
            // cod_plano e origem estão em new_values (texto): filtrados em PHP.
            ->filter(fn (Audit $a) => $plano === '' || (string) ($a->new_values['cod_plano'] ?? '') === $plano)
            ->filter(fn (Audit $a) => $origem === '' || ($a->new_values['origem'] ?? '') === $origem);

        $usuarios = User::with('roles:id,name')
            ->whereIn('id', $audits->where('user_type', User::class)->pluck('user_id')->filter()->unique())
            ->get(['id', 'name'])
            ->keyBy('id');

        return $audits->map(function (Audit $audit) use ($usuarios) {
            $dados = $audit->new_values ?? [];
            $user = $audit->user_type === User::class ? $usuarios->get($audit->user_id) : null;
            $quando = $audit->created_at->copy()->setTimezone(Formatar::FUSO);

            return [
                'usuario_key' => $audit->user_id ? 'u'.$audit->user_id : 'sem',
                // Nome atual do usuário; senão o gravado no registro.
                'usuario' => $user?->name ?? ($dados['usuario'] ?? ($audit->user_id ? "Usuário #{$audit->user_id}" : self::SEM_USUARIO)),
                'perfis' => $user ? $user->roles->pluck('name')->implode(', ') : '',
                'paciente' => $dados['paciente'] ?? (isset($dados['paciente_id']) ? "#{$dados['paciente_id']}" : '-'),
                'cod_plano' => (string) ($dados['cod_plano'] ?? ''),
                'plano' => $dados['plano'] ?? '-',
                'origem_key' => $dados['origem'] ?? '',
                'origem' => self::ORIGENS[$dados['origem'] ?? ''] ?? ($dados['origem'] ?? '-'),
                'dia' => $quando->toDateString(),
                'data' => $quando->format('d/m/Y H:i'),
                'data_ordem' => $quando->timestamp,
            ];
        })->values();
    }

    /**
     * Usuários do maior para o menor, com o total por plano.
     */
    private function porUsuario(Collection $registros, array $planos, \Closure $percentual): array
    {
        return $registros->groupBy('usuario_key')
            ->map(fn (Collection $itens) => [
                'usuario' => $itens->first()['usuario'],
                'perfis' => $itens->first()['perfis'],
                'total' => $itens->count(),
                'percentual' => $percentual($itens->count()),
                'planos' => collect($planos)
                    ->mapWithKeys(fn ($p) => [$p['value'] => $itens->where('cod_plano', (string) $p['value'])->count()])
                    ->all(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Registros por dia (períodos curtos) ou por semana, incluindo os zerados.
     *
     * @return array{tipo: string, itens: array<int, array{label: string, total: int}>}
     */
    private function evolucao(Collection $registros, Carbon $de, Carbon $ate): array
    {
        $porDia = $registros->countBy('dia');
        $diario = $de->diffInDays($ate) < self::DIAS_EVOLUCAO_DIARIA;
        $itens = [];

        for ($dia = $de->copy(); $dia->lte($ate); $dia->addDay()) {
            $chave = $diario ? $dia->format('d/m') : 'Semana de '.$dia->copy()->startOfWeek()->max($de)->format('d/m');
            $itens[$chave] = ($itens[$chave] ?? 0) + ($porDia[$dia->toDateString()] ?? 0);
        }

        return [
            'tipo' => $diario ? 'dia' : 'semana',
            'itens' => collect($itens)->map(fn ($total, $label) => compact('label', 'total'))->values()->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function descreverFiltros(array $filtros): array
    {
        $descricao = [];

        if (filled($filtros['usuario'] ?? null)) {
            $descricao[] = 'Usuário: '.(User::find($filtros['usuario'])?->name ?? "#{$filtros['usuario']}");
        }
        if (filled($filtros['perfil'] ?? null)) {
            $descricao[] = 'Perfil: '.$filtros['perfil'];
        }
        if (filled($filtros['plano'] ?? null)) {
            $descricao[] = 'Plano: '.($this->desempenhoService->planoLabel($filtros['plano']) ?? $filtros['plano']);
        }
        if (filled($filtros['origem'] ?? null)) {
            $descricao[] = 'Origem: '.(self::ORIGENS[$filtros['origem']] ?? $filtros['origem']);
        }

        return $descricao;
    }
}
