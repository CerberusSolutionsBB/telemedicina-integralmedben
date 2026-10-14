<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\CupomRequest;
use App\Models\Cupom;
use App\Models\Tenant;
use App\Services\Cupom\CupomPdfService;
use App\Services\Cupom\CupomService;
use App\Support\Planos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cupons de desconto no painel central: globais e de qualquer parceiro.
 */
class CupomController extends Controller
{
    public function __construct(private readonly CupomService $cupomService) {}

    public function index(): Response
    {
        return Inertia::render('Financeiro/Cupons/Index', [
            'cupons' => Cupom::with(['planos', 'tenant.details'])->withExists('usosRegistrados')->latest()->get()
                ->map(fn (Cupom $cupom) => $this->cupomService->paraTela($cupom))
                ->values(),
            'parceiros' => $this->parceiros(),
            'opcoes' => $this->opcoes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Financeiro/Cupons/Create', [
            'parceiros' => $this->parceiros(),
            'opcoes' => $this->opcoes(),
            'breadcrumbs' => [
                ['label' => 'Cupons', 'href' => route('financeiro.cupons.index')],
                ['label' => 'Novo cupom', 'href' => null],
            ],
        ]);
    }

    public function store(CupomRequest $request): RedirectResponse
    {
        $cupom = $this->cupomService->salvar($request->validated());

        return redirect()->route('financeiro.cupons.show', $cupom)->with('success', "Cupom {$cupom->codigo} criado.");
    }

    public function show(Cupom $cupom): Response
    {
        $cupom->load(['planos', 'tenant.details'])->loadExists('usosRegistrados');

        return Inertia::render('Financeiro/Cupons/Show', [
            'cupom' => $this->cupomService->paraTela($cupom),
            'usos' => $this->cupomService->usosParaTela($cupom),
            'breadcrumbs' => [
                ['label' => 'Cupons', 'href' => route('financeiro.cupons.index')],
                ['label' => $cupom->codigo, 'href' => null],
            ],
        ]);
    }

    public function edit(Cupom $cupom): Response
    {
        $cupom->load(['planos', 'tenant.details']);

        return Inertia::render('Financeiro/Cupons/Edit', [
            'cupom' => $this->cupomService->paraTela($cupom),
            'parceiros' => $this->parceiros(),
            'opcoes' => $this->opcoes(),
            'breadcrumbs' => [
                ['label' => 'Cupons', 'href' => route('financeiro.cupons.index')],
                ['label' => $cupom->codigo, 'href' => route('financeiro.cupons.show', $cupom)],
                ['label' => 'Editar', 'href' => null],
            ],
        ]);
    }

    public function update(CupomRequest $request, Cupom $cupom): RedirectResponse
    {
        $this->cupomService->salvar($request->validated(), $cupom);

        return redirect()->route('financeiro.cupons.show', $cupom)->with('success', "Cupom {$cupom->codigo} atualizado.");
    }

    public function destroy(Cupom $cupom): RedirectResponse
    {
        try {
            $this->cupomService->excluir($cupom);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('financeiro.cupons.index')->with('success', "Cupom {$cupom->codigo} excluído.");
    }

    public function status(Request $request, Cupom $cupom): RedirectResponse
    {
        $dados = $request->validate(['status' => ['required', Rule::in(array_keys(Cupom::STATUS))]]);

        try {
            $this->cupomService->alterarStatus($cupom, $dados['status']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "Cupom {$cupom->codigo}: ".mb_strtolower(Cupom::STATUS[$dados['status']]).'.');
    }

    /**
     * Voucher do cupom em PDF (abre no navegador para imprimir ou salvar).
     */
    public function pdf(Cupom $cupom, CupomPdfService $cupomPdf): HttpResponse
    {
        return $cupomPdf->gerar($cupom)->stream('cupom-'.$cupom->codigo.'.pdf');
    }

    /**
     * @return array<int, array{id: string, nome: string}>
     */
    private function parceiros(): array
    {
        return Tenant::whereNull('deleted_at')->with('details')->orderBy('id')->get()
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'nome' => $tenant->details->first()?->descricao ?? ($tenant->tenant_domain ?? $tenant->id),
            ])
            ->all();
    }

    /**
     * Opções dos selects do formulário e filtros.
     *
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return self::opcoesFormulario();
    }

    /**
     * @return array<string, mixed>
     */
    public static function opcoesFormulario(): array
    {
        $paraOpcoes = fn (array $mapa) => collect($mapa)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();

        return [
            'tipos' => $paraOpcoes(Cupom::TIPOS),
            'status' => $paraOpcoes(Cupom::STATUS),
            'aplicaveis' => $paraOpcoes(Cupom::APLICAVEIS),
            'origens' => $paraOpcoes(Cupom::ORIGENS),
            'planos' => collect(Planos::options())->map(fn ($p) => ['value' => $p['value'], 'label' => $p['label']])->all(),
            'limites' => CupomRequest::LIMITES,
        ];
    }
}
