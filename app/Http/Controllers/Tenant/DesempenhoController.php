<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesempenhoRequest;
use App\Models\Desempenho;
use App\Models\User;
use App\Services\Tenant\DesempenhoService;
use App\Services\Tenant\ProducaoUsuariosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DesempenhoController extends Controller
{
    public function __construct(
        private readonly DesempenhoService $desempenhoService,
        private readonly ProducaoUsuariosService $producao,
    ) {}

    public function index(): Response
    {
        $desempenhos = Desempenho::with('roles:id,name', 'usuarios:id,name')
            ->orderByDesc('prazo')
            ->get()
            ->map(fn (Desempenho $desempenho) => [
                ...$this->resumo($desempenho),
                'progresso' => collect($this->desempenhoService->progresso($desempenho, tenant('id')))->except(['usuarios', 'por_plano'])->all(),
            ]);

        return Inertia::render('Desempenho/Index', [
            'desempenhos' => $desempenhos,
            'statusLabels' => DesempenhoService::STATUS,
            'relatorioOpcoes' => $this->producao->opcoes(tenant('id')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Desempenho/Create', [
            'breadcrumbs' => [
                ['label' => 'Relatório', 'href' => route('desempenho.index')],
                ['label' => 'Nova meta', 'href' => null],
            ],
            ...$this->opcoes(),
        ]);
    }

    public function store(DesempenhoRequest $request)
    {
        $desempenho = DB::transaction(function () use ($request) {
            $desempenho = Desempenho::create([...$request->dados(), 'user_id' => $request->user()?->id]);
            $desempenho->roles()->sync($request->validated('roles'));
            $desempenho->usuarios()->sync($request->validated('usuarios', []));

            return $desempenho;
        });

        return redirect()->route('desempenho.show', $desempenho)->with('success', 'Meta criada com sucesso.');
    }

    public function show(Desempenho $desempenho): Response
    {
        $desempenho->load('roles:id,name', 'criador:id,name', 'usuarios:id,name');

        return Inertia::render('Desempenho/Show', [
            'breadcrumbs' => [
                ['label' => 'Relatório', 'href' => route('desempenho.index')],
                ['label' => $desempenho->titulo, 'href' => null],
            ],
            'desempenho' => [...$this->resumo($desempenho), 'criador' => $desempenho->criador?->name],
            'progresso' => $this->desempenhoService->progresso($desempenho, tenant('id')),
            'statusLabels' => DesempenhoService::STATUS,
        ]);
    }

    public function edit(Desempenho $desempenho): Response
    {
        $desempenho->load('roles:id', 'usuarios:id,name,email', 'usuarios.roles:id,name');

        return Inertia::render('Desempenho/Edit', [
            'breadcrumbs' => [
                ['label' => 'Relatório', 'href' => route('desempenho.index')],
                ['label' => $desempenho->titulo, 'href' => route('desempenho.show', $desempenho)],
                ['label' => 'Editar', 'href' => null],
            ],
            'desempenho' => [
                ...$desempenho->only(['id', 'titulo', 'descricao', 'funcao', 'escopo_plano', 'cod_plano', 'tipo_meta', 'meta']),
                'data_inicio' => $desempenho->data_inicio->format('Y-m-d'),
                'prazo' => $desempenho->prazo->format('Y-m-d'),
                'roles' => $desempenho->roles->pluck('id')->all(),
                'usuarios' => $desempenho->usuarios->map(fn (User $usuario) => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'roles' => $usuario->roles->pluck('id')->all(),
                ])->values()->all(),
            ],
            ...$this->opcoes(),
        ]);
    }

    public function update(DesempenhoRequest $request, Desempenho $desempenho)
    {
        DB::transaction(function () use ($request, $desempenho) {
            $desempenho->update($request->dados());
            $desempenho->roles()->sync($request->validated('roles'));
            $desempenho->usuarios()->sync($request->validated('usuarios', []));
        });

        return redirect()->route('desempenho.show', $desempenho)->with('success', 'Meta atualizada com sucesso.');
    }

    public function destroy(Desempenho $desempenho)
    {
        $desempenho->delete();

        return redirect()->route('desempenho.index')->with('success', 'Meta excluída com sucesso.');
    }

    /**
     * Busca os usuários de um perfil para o modal de seleção da meta.
     */
    public function usuarios(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $perfil = Role::findOrFail($dados['role_id']);
        $busca = trim((string) ($dados['q'] ?? ''));

        $consulta = User::role($perfil->name)
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca) {
                $query->where('name', 'like', "%{$busca}%")->orWhere('email', 'like', "%{$busca}%");
            }))
            ->orderBy('name');

        return response()->json([
            'total' => $consulta->count(),
            'usuarios' => $consulta->limit(50)->get(['id', 'name', 'email']),
        ]);
    }

    private function opcoes(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'funcoes' => collect(Desempenho::FUNCOES)->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'tipos' => collect(Desempenho::TIPOS)->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'planos' => $this->desempenhoService->planos(tenant('id')),
            'limites' => Desempenho::LIMITES,
        ];
    }

    private function resumo(Desempenho $desempenho): array
    {
        return [
            'id' => $desempenho->id,
            'titulo' => $desempenho->titulo,
            'descricao' => $desempenho->descricao,
            'funcao' => Desempenho::FUNCOES[$desempenho->funcao] ?? $desempenho->funcao,
            'funcao_key' => $desempenho->funcao,
            'plano' => $desempenho->escopo_plano === Desempenho::ESCOPO_PLANO
                ? $this->desempenhoService->planoLabel($desempenho->cod_plano)
                : 'Todos os planos',
            'tipo_meta' => $desempenho->tipo_meta,
            'tipo_label' => Desempenho::TIPOS[$desempenho->tipo_meta] ?? $desempenho->tipo_meta,
            'meta' => $desempenho->meta,
            'meta_valor' => $desempenho->meta_valor !== null ? (float) $desempenho->meta_valor : null,
            'data_inicio' => $desempenho->data_inicio->format('d/m/Y'),
            'prazo' => $desempenho->prazo->format('d/m/Y'),
            'roles' => $desempenho->roles->pluck('name')->all(),
            'usuarios' => $desempenho->usuarios->pluck('name')->all(),
        ];
    }
}
