<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Requests\ProcessoRequest;
use App\Models\Processo;
use App\Models\Servico;
use App\Services\Tenant\ProcessoCupomService;
use App\Support\ModulosParceiro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Processos do parceiro (painel do tenant), auditados: valor, campos personalizados
 * e serviços vinculados.
 */
class ProcessoController extends CadastroIconeController
{
    protected string $model = Processo::class;

    protected string $paginas = 'Tenant/Processo';

    protected string $rotas = 'processos';

    protected string $plural = 'Processos';

    protected string $singular = 'Processo';

    protected string $novo = 'Novo processo';

    protected string $request = ProcessoRequest::class;

    /**
     * Prévia do cupom de benefício no processo (aba Processos do Relatório). Nada é gravado.
     */
    public function cupom(Request $request, int $id, ProcessoCupomService $cupons): JsonResponse
    {
        $dados = $request->validate(['cupom' => ['required', 'string', 'max:20']]);

        if (! ModulosParceiro::habilitado(tenant('id'), 'cupons_beneficio')) {
            return response()->json(['valido' => false, 'mensagem' => 'Cupons de benefício não estão habilitados para este parceiro.'], 422);
        }

        try {
            $calculo = $cupons->aplicar(Processo::findOrFail($id), $dados['cupom']);
        } catch (ValidationException $e) {
            return response()->json(['valido' => false, 'mensagem' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['valido' => true, ...$calculo]);
    }

    /**
     * Registra o uso do cupom no processo, com o usuário logado (histórico no detalhe).
     */
    public function usarCupom(Request $request, int $id, ProcessoCupomService $cupons): JsonResponse
    {
        $dados = $request->validate(['cupom' => ['required', 'string', 'max:20']]);

        if (! ModulosParceiro::habilitado(tenant('id'), 'cupons_beneficio')) {
            return response()->json(['registrado' => false, 'mensagem' => 'Cupons de benefício não estão habilitados para este parceiro.'], 422);
        }

        try {
            $uso = $cupons->registrar(Processo::findOrFail($id), $dados['cupom'], $request->user());
        } catch (ValidationException $e) {
            return response()->json(['registrado' => false, 'mensagem' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['registrado' => true, 'uso' => $cupons->paraTela($uso)]);
    }

    /**
     * @param  Processo  $item
     */
    protected function extrasDetalhe(Model $item): array
    {
        $cupons = app(ProcessoCupomService::class);

        return [
            'usosCupom' => $item->usosCupom()->with(['cupom:id,titulo', 'usuario:id,name'])->limit(100)->get()
                ->map(fn ($uso) => $cupons->paraTela($uso))
                ->all(),
        ];
    }

    protected function consultaLista(): Builder
    {
        return parent::consultaLista()->with('servicos:id,titulo,icone,status');
    }

    /**
     * Sincroniza os serviços só quando a escolha mudou (auditSync grava o que entrou e saiu).
     *
     * @param  Processo  $item
     */
    protected function salvarRelacoes(Model $item, array $dados): void
    {
        $novos = collect($dados['servicos'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
        $atuais = $item->servicos()->pluck('servicos.id')->sort()->values()->all();

        if ($novos !== $atuais) {
            $item->auditSync('servicos', $novos, true, ['servicos.id', 'servicos.titulo']);
        }

        $item->unsetRelation('servicos');
    }

    /**
     * @param  Processo  $item
     */
    protected function paraTela(Model $item): array
    {
        return [
            ...parent::paraTela($item),
            'valor' => (float) $item->valor,
            'valor_formatado' => $item->valorFormatado(),
            'campo_status' => (bool) $item->campo_status,
            'servicos' => $item->servicos
                ->map(fn (Servico $servico) => $servico->only(['id', 'titulo', 'icone', 'status']))
                ->values()
                ->all(),
        ];
    }

    protected function opcoesExtras(): array
    {
        return [
            // Inativos também aparecem (um processo já vinculado a eles precisa continuar editável).
            'servicos' => Servico::orderBy('titulo')->get(['id', 'titulo', 'icone', 'status'])->all(),
        ];
    }

    protected function camposHistorico(): array
    {
        return [
            'valor' => 'Valor',
            'campo_status' => 'Campos personalizados',
            'servicos' => 'Serviços',
        ];
    }

    protected function valorHistorico(string $campo, mixed $valor): ?string
    {
        return match ($campo) {
            'valor' => Processo::moeda($valor),
            'campo_status' => (bool) $valor ? 'Ligados' : 'Desligados',
            // auditSync: serviços que saíram (antes) e que entraram (depois); "—" = nenhum.
            'servicos' => collect($valor)->pluck('titulo')->filter()->implode(', ') ?: '—',
            default => null,
        };
    }
}
