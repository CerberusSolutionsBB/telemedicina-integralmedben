<?php

namespace App\Services\Tenant;

use App\Models\CupomBeneficio;
use App\Models\Tenant;
use App\Support\Formatar;
use App\Support\Planos;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Voucher do cupom de benefício em PDF, no mesmo modelo do cupom de venda
 * (pdf/cupom.blade.php): o beneficiário leva impresso ou no celular e apresenta
 * no convênio/serviço. Só informação para o cliente.
 */
class CupomBeneficioPdfService
{
    public function gerar(CupomBeneficio $cupom, string $tenantId): DomPdf
    {
        $dados = $this->dados($cupom, $tenantId);

        $pdf = Pdf::loadView('pdf.cupom', $dados)->setPaper('a4');

        return RodapePdf::aplicar(
            $pdf,
            'Gerado por '.(auth()->user()?->name ?? 'sistema').' em '.Formatar::dataHora(now()),
            $dados['rotulo'].' · Cupom '.$cupom->codigo,
            30,
        );
    }

    /**
     * Conteúdo do voucher.
     *
     * @return array<string, mixed> variáveis de pdf/cupom.blade.php
     */
    public function dados(CupomBeneficio $cupom, string $tenantId): array
    {
        $cupom->loadMissing(['convenio:id,titulo', 'servico:id,titulo']);
        $tenant = Tenant::with('details')->find($tenantId);
        $parceiro = $tenant ? ($tenant->name ?: ($tenant->details->first()?->descricao ?? $tenant->id)) : $tenantId;
        $onde = collect([$cupom->convenio?->titulo, $cupom->servico?->titulo])->filter()->implode(' e em ');

        return [
            'rotulo' => $parceiro,
            'titulo' => 'Cupom de benefício',
            'codigo' => $cupom->codigo,
            'desconto' => $cupom->descontoFormatado().' OFF',
            'textoDesconto' => $onde ? "em {$onde}" : $cupom->titulo,
            'validade' => $cupom->prazo ? 'Válido até '.Formatar::data($cupom->prazo) : 'Sem prazo de validade',
            'condicoes' => $this->condicoes($cupom),
            'comoUsar' => [
                'Apresente este cupom (impresso ou no celular) ou informe o código :codigo'.($onde ? " em {$onde}" : '').'.',
                'Diga que é beneficiário '.$parceiro.'.',
                'O desconto é aplicado na hora do atendimento ou da compra.',
            ],
            'aviso' => $this->aviso($cupom),
            'logoBase64' => $this->logo($tenant?->photo_path),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function condicoes(CupomBeneficio $cupom): array
    {
        $plano = $cupom->cod_plano ? (collect(Planos::options())->pluck('label', 'value')[$cupom->cod_plano] ?? null) : null;

        return array_values(array_filter([
            $plano ? "Válido para beneficiários do plano {$plano}." : 'Válido para beneficiários de qualquer plano.',
            // A descrição é o texto livre do parceiro (regras, horários...): uma condição por linha.
            ...preg_split('/\R+/', trim((string) $cupom->descricao)),
        ]));
    }

    private function aviso(CupomBeneficio $cupom): ?string
    {
        return match (true) {
            ! $cupom->status => 'Atenção: este cupom está inativo e não pode ser usado.',
            $cupom->vencido() => 'Atenção: este cupom venceu em '.Formatar::data($cupom->prazo).' e não pode ser usado.',
            default => null,
        };
    }

    private function logo(?string $caminhoRelativo): ?string
    {
        if (! $caminhoRelativo) {
            return null;
        }

        $caminho = base_path('storage/app/public/'.$caminhoRelativo);

        return file_exists($caminho)
            ? 'data:'.mime_content_type($caminho).';base64,'.base64_encode(file_get_contents($caminho))
            : null;
    }
}
