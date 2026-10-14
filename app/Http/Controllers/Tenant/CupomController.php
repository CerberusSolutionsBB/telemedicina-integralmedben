<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Financeiro\CupomController as CupomCentralController;
use App\Http\Requests\CupomRequest;
use App\Models\Cupom;
use App\Services\Cupom\CupomPdfService;
use App\Services\Cupom\CupomService;
use App\Support\Planos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cupons do próprio parceiro (painel do tenant). Só existe quando o admin define
 * o teto de desconto do parceiro; nunca enxerga cupons globais ou de outro tenant.
 */
class CupomController extends Controller
{
    public function __construct(private readonly CupomService $cupomService) {}

    public function index(): Response
    {
        $this->garantirHabilitado();

        return Inertia::render('Tenant/Cupons/Index', [
            'cupons' => Cupom::doTenant($this->tenantId())->with('planos')->withExists('usosRegistrados')->latest()->get()
                ->map(fn (Cupom $cupom) => $this->cupomService->paraTela($cupom))
                ->values(),
            'opcoes' => CupomCentralController::opcoesFormulario(),
            'teto' => $this->teto(),
        ]);
    }

    public function create(): Response
    {
        $this->garantirHabilitado();

        return Inertia::render('Tenant/Cupons/Create', [
            'opcoes' => CupomCentralController::opcoesFormulario(),
            'teto' => $this->teto(),
            'breadcrumbs' => [
                ['label' => 'Cupons', 'href' => route('cupons.index')],
                ['label' => 'Novo cupom', 'href' => null],
            ],
        ]);
    }

    public function store(CupomRequest $request): RedirectResponse
    {
        $this->garantirHabilitado();

        $cupom = $this->cupomService->salvar($request->validated(), tenantFixo: $this->tenantId());

        return redirect()->route('cupons.show', $cupom->id)->with('success', "Cupom {$cupom->codigo} criado.");
    }

    public function show(int $cupom): Response
    {
        $this->garantirHabilitado();

        $cupom = $this->doParceiro($cupom)->load('planos')->loadExists('usosRegistrados');

        return Inertia::render('Tenant/Cupons/Show', [
            'cupom' => $this->cupomService->paraTela($cupom),
            'usos' => $this->cupomService->usosParaTela($cupom),
            'breadcrumbs' => [
                ['label' => 'Cupons', 'href' => route('cupons.index')],
                ['label' => $cupom->codigo, 'href' => null],
            ],
        ]);
    }

    public function edit(int $cupom): Response
    {
        $this->garantirHabilitado();

        $cupom = $this->doParceiro($cupom)->load('planos');

        return Inertia::render('Tenant/Cupons/Edit', [
            'cupom' => $this->cupomService->paraTela($cupom),
            'opcoes' => CupomCentralController::opcoesFormulario(),
            'teto' => $this->teto(),
            'breadcrumbs' => [
                ['label' => 'Cupons', 'href' => route('cupons.index')],
                ['label' => $cupom->codigo, 'href' => route('cupons.show', $cupom->id)],
                ['label' => 'Editar', 'href' => null],
            ],
        ]);
    }

    public function update(CupomRequest $request, int $cupom): RedirectResponse
    {
        $this->garantirHabilitado();

        $cupom = $this->doParceiro($cupom);

        $this->cupomService->salvar($request->validated(), $cupom, $this->tenantId());

        return redirect()->route('cupons.show', $cupom->id)->with('success', "Cupom {$cupom->codigo} atualizado.");
    }

    public function destroy(int $cupom): RedirectResponse
    {
        $this->garantirHabilitado();

        $cupom = $this->doParceiro($cupom);
        try {
            $this->cupomService->excluir($cupom);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('cupons.index')->with('success', "Cupom {$cupom->codigo} excluído.");
    }

    public function status(Request $request, int $cupom): RedirectResponse
    {
        $this->garantirHabilitado();

        $cupom = $this->doParceiro($cupom);
        $dados = $request->validate(['status' => ['required', Rule::in(array_keys(Cupom::STATUS))]]);

        try {
            $this->cupomService->alterarStatus($cupom, $dados['status']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "Cupom {$cupom->codigo}: ".mb_strtolower(Cupom::STATUS[$dados['status']]).'.');
    }

    /**
     * Voucher do cupom do próprio parceiro em PDF.
     */
    public function pdf(int $cupom, CupomPdfService $cupomPdf): HttpResponse
    {
        $this->garantirHabilitado();

        $cupom = $this->doParceiro($cupom);

        return $cupomPdf->gerar($cupom)->stream('cupom-'.$cupom->codigo.'.pdf');
    }

    /**
     * Prévia do desconto no cadastro do beneficiário (botão "Aplicar").
     */
    public function validar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'cupom' => ['required', 'string', 'max:50'],
            'cod_plano' => ['required', 'string', Rule::in(Planos::codigos())],
            'cpf' => ['nullable', 'string', 'max:14'],
        ]);

        try {
            $calculo = $this->cupomService->validar($dados['cupom'], $this->tenantId(), $dados['cod_plano'], $dados['cpf'] ?? null);
        } catch (ValidationException $e) {
            return response()->json(['valido' => false, 'mensagem' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'valido' => true,
            'valor_original' => $calculo['valor_original'],
            'desconto' => $calculo['desconto'],
            'valor_final' => $calculo['valor_final'],
        ]);
    }

    /**
     * Cupom do próprio parceiro; de outro tenant ou global = 404.
     */
    private function doParceiro(int $id): Cupom
    {
        return Cupom::doTenant($this->tenantId())->findOrFail($id);
    }

    /**
     * Sem teto definido pelo admin, o parceiro não usa cupons.
     */
    private function garantirHabilitado(): void
    {
        abort_if($this->teto() === null, 403, 'Cupons não estão habilitados para este parceiro.');
    }

    private function teto(): ?float
    {
        return $this->cupomService->tetoDoTenant($this->tenantId());
    }

    private function tenantId(): string
    {
        return (string) tenant('id');
    }
}
