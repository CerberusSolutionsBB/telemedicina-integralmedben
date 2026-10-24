<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\CadastroIconeRequest;
use App\Services\Tenant\CadastroIconeService;
use App\Support\IconesLucide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD dos cadastros do parceiro com título, descrição, ícone e status
 * (serviços, convênios). Cada módulo só define o model, as telas e os nomes.
 */
abstract class CadastroIconeController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Pasta das páginas Inertia (ex.: Tenant/Servico). */
    protected string $paginas;

    /** Prefixo dos nomes de rota (ex.: servicos). */
    protected string $rotas;

    /** Nome no plural para o breadcrumb (ex.: Serviços). */
    protected string $plural;

    /** Nome no singular para as mensagens (ex.: Serviço). */
    protected string $singular;

    /** Texto do breadcrumb de criação (ex.: Novo serviço). */
    protected string $novo;

    /** @var class-string<CadastroIconeRequest> Validação (módulos com campos extras estendem). */
    protected string $request = CadastroIconeRequest::class;

    public function __construct(protected readonly CadastroIconeService $cadastroService) {}

    public function index(): Response
    {
        return Inertia::render("{$this->paginas}/Index", [
            'itens' => $this->itens(),
        ]);
    }

    /**
     * Itens da lista no formato das telas (também usado fora do módulo, ex.: aba
     * Cupons de benefício da tela Relatório).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function itens(): Collection
    {
        return $this->consultaLista()->get()
            ->map(fn (Model $item) => $this->paraTela($item))
            ->values()
            ->toBase();
    }

    public function create(): Response
    {
        return Inertia::render("{$this->paginas}/Create", [
            ...$this->opcoes(),
            'breadcrumbs' => [
                ['label' => $this->plural, 'href' => route("{$this->rotas}.index")],
                ['label' => $this->novo, 'href' => null],
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        $dados = $this->dadosValidados();
        $item = DB::transaction(function () use ($dados) {
            $item = $this->model::create($dados);
            $this->salvarRelacoes($item, $dados);

            return $item;
        });

        return redirect()->route("{$this->rotas}.show", $item->getKey())->with('success', "{$this->singular} \"{$item->titulo}\" criado.");
    }

    public function show(int $id): Response
    {
        $item = $this->model::findOrFail($id);

        return Inertia::render("{$this->paginas}/Show", [
            'item' => $this->paraTela($item),
            'historico' => $this->cadastroService->historico(
                $item,
                (string) tenant('id'),
                $this->camposHistorico(),
                fn (string $campo, mixed $valor) => $this->valorHistorico($campo, $valor),
            ),
            ...$this->extrasDetalhe($item),
            'breadcrumbs' => [
                ['label' => $this->plural, 'href' => route("{$this->rotas}.index")],
                ['label' => $item->titulo, 'href' => null],
            ],
        ]);
    }

    public function edit(int $id): Response
    {
        $item = $this->model::findOrFail($id);

        return Inertia::render("{$this->paginas}/Edit", [
            'item' => $this->paraTela($item),
            ...$this->opcoes(),
            'breadcrumbs' => [
                ['label' => $this->plural, 'href' => route("{$this->rotas}.index")],
                ['label' => $item->titulo, 'href' => route("{$this->rotas}.show", $item->getKey())],
                ['label' => 'Editar', 'href' => null],
            ],
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $item = $this->model::findOrFail($id);
        $dados = $this->dadosValidados();

        DB::transaction(function () use ($item, $dados) {
            $item->update($dados);
            $this->salvarRelacoes($item, $dados);
        });

        return redirect()->route("{$this->rotas}.show", $item->getKey())->with('success', "{$this->singular} \"{$item->titulo}\" atualizado.");
    }

    public function status(Request $request, int $id): RedirectResponse
    {
        $item = $this->model::findOrFail($id);
        $dados = $request->validate(['status' => ['required', 'boolean']]);

        $item->update(['status' => (bool) $dados['status']]);

        return back()->with('success', "{$this->singular} \"{$item->titulo}\" ".($item->status ? 'ativado' : 'inativado').'.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = $this->model::findOrFail($id);

        try {
            $item->delete();
        } catch (QueryException) {
            // Chave estrangeira (ex.: convênio usado por um cupom de benefício).
            return back()->with('error', "{$this->singular} \"{$item->titulo}\" está em uso e não pode ser excluído. Inative-o.");
        }

        return redirect()->route("{$this->rotas}.index")->with('success', "{$this->singular} \"{$item->titulo}\" excluído.");
    }

    /**
     * Consulta da lista (módulos com relações sobrescrevem para carregar com with()).
     */
    protected function consultaLista(): Builder
    {
        return $this->model::query()->orderBy('titulo');
    }

    /**
     * Grava as relações do item (ex.: serviços de um processo), na mesma transação
     * do create/update. Os dados validados de relação não estão no $fillable.
     *
     * @param  array<string, mixed>  $dados
     */
    protected function salvarRelacoes(Model $item, array $dados): void {}

    /**
     * Item no formato das telas; módulos com campos extras acrescentam os seus.
     *
     * @return array<string, mixed>
     */
    protected function paraTela(Model $item): array
    {
        return $this->cadastroService->paraTela($item);
    }

    /**
     * Dados extras da tela de detalhe (ex.: usos de cupom do processo).
     *
     * @return array<string, mixed>
     */
    protected function extrasDetalhe(Model $item): array
    {
        return [];
    }

    /**
     * Opções extras do formulário (ex.: planos, convênios).
     *
     * @return array<string, mixed>
     */
    protected function opcoesExtras(): array
    {
        return [];
    }

    /**
     * Campos extras exibidos no histórico (campo => rótulo).
     *
     * @return array<string, string>
     */
    protected function camposHistorico(): array
    {
        return [];
    }

    /**
     * Valor de um campo extra no histórico; null = formatação padrão.
     */
    protected function valorHistorico(string $campo, mixed $valor): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidados(): array
    {
        return app($this->request)->validated();
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return [
            'icones' => IconesLucide::opcoes(),
            'limites' => CadastroIconeRequest::LIMITES,
            ...$this->opcoesExtras(),
        ];
    }
}
