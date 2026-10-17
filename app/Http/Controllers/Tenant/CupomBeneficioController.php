<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Requests\CupomBeneficioRequest;
use App\Models\Convenio;
use App\Models\CupomBeneficio;
use App\Models\Servico;
use App\Services\Tenant\CupomBeneficioPdfService;
use App\Support\Formatar;
use App\Support\Planos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response as HttpResponse;

/**
 * Cupons de benefício do parceiro (painel do tenant), auditados: desconto num
 * convênio ou serviço, opcionalmente só para um plano, até o prazo.
 */
class CupomBeneficioController extends CadastroIconeController
{
    protected string $model = CupomBeneficio::class;

    protected string $paginas = 'Tenant/Cupom';

    protected string $rotas = 'cupons-beneficio';

    protected string $plural = 'Cupons de benefício';

    protected string $singular = 'Cupom';

    protected string $novo = 'Novo cupom de benefício';

    protected string $request = CupomBeneficioRequest::class;

    /**
     * Voucher do cupom em PDF (mesmo modelo do cupom de venda), para o beneficiário levar.
     */
    public function pdf(int $id, CupomBeneficioPdfService $pdf): HttpResponse
    {
        $cupom = CupomBeneficio::findOrFail($id);

        return $pdf->gerar($cupom, (string) tenant('id'))->stream('cupom-'.$cupom->codigo.'.pdf');
    }

    protected function consultaLista(): Builder
    {
        return parent::consultaLista()->with(['convenio:id,titulo,icone', 'servico:id,titulo,icone']);
    }

    /**
     * @param  CupomBeneficio  $item
     */
    protected function paraTela(Model $item): array
    {
        return [
            ...parent::paraTela($item),
            'codigo' => $item->codigo,
            'cod_plano' => $item->cod_plano,
            'plano' => $item->cod_plano ? ($this->nomesPlanos()[$item->cod_plano] ?? "Plano {$item->cod_plano}") : null,
            'convenio_id' => $item->convenio_id,
            'convenio' => $item->convenio ? ['titulo' => $item->convenio->titulo, 'icone' => $item->convenio->icone] : null,
            'servico_id' => $item->servico_id,
            'servico' => $item->servico ? ['titulo' => $item->servico->titulo, 'icone' => $item->servico->icone] : null,
            'tipo_desconto' => $item->tipo_desconto,
            'valor_desconto' => (float) $item->valor_desconto,
            'desconto' => $item->descontoFormatado(),
            'prazo' => $item->prazo?->format('Y-m-d'),
            'prazo_formatado' => Formatar::data($item->prazo),
            'vencido' => $item->vencido(),
        ];
    }

    protected function opcoesExtras(): array
    {
        return [
            'planos' => collect(Planos::options())->map(fn ($p) => ['value' => $p['value'], 'label' => $p['label']])->all(),
            // Inativos também aparecem (um cupom já vinculado a eles precisa continuar editável).
            'convenios' => Convenio::orderBy('titulo')->get(['id', 'titulo', 'status'])->all(),
            'servicos' => Servico::orderBy('titulo')->get(['id', 'titulo', 'status'])->all(),
            'tiposDesconto' => collect(CupomBeneficio::TIPOS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all(),
        ];
    }

    protected function camposHistorico(): array
    {
        return [
            'codigo' => 'Código',
            'cod_plano' => 'Plano',
            'convenio_id' => 'Convênio',
            'servico_id' => 'Serviço',
            'tipo_desconto' => 'Tipo de desconto',
            'valor_desconto' => 'Desconto',
            'prazo' => 'Prazo',
        ];
    }

    protected function valorHistorico(string $campo, mixed $valor): ?string
    {
        return match ($campo) {
            'cod_plano' => $this->nomesPlanos()[(string) $valor] ?? "Plano {$valor}",
            'convenio_id' => Convenio::whereKey($valor)->value('titulo') ?? "Convênio #{$valor} (excluído)",
            'servico_id' => Servico::whereKey($valor)->value('titulo') ?? "Serviço #{$valor} (excluído)",
            'tipo_desconto' => CupomBeneficio::TIPOS[$valor] ?? (string) $valor,
            'valor_desconto' => number_format((float) $valor, 2, ',', '.'),
            'prazo' => Formatar::data($valor),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function nomesPlanos(): array
    {
        return collect(Planos::options())->pluck('label', 'value')->all();
    }
}
