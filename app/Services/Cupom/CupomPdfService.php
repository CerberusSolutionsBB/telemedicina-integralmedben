<?php

namespace App\Services\Cupom;

use App\Models\Cupom;
use App\Support\Formatar;
use App\Support\Planos;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Voucher do cupom em PDF (para imprimir ou enviar): código em destaque,
 * desconto, validade, condições e como usar.
 */
class CupomPdfService
{
    public function gerar(Cupom $cupom): DomPdf
    {
        $cupom->loadMissing(['planos', 'tenant.details']);
        $tenant = $cupom->tenant;
        $parceiro = $tenant ? ($tenant->name ?: ($tenant->details->first()?->descricao ?? $tenant->id)) : null;

        $pdf = Pdf::loadView('pdf.cupom', [
            'cupom' => $cupom,
            'parceiro' => $parceiro,
            'desconto' => $this->desconto($cupom),
            'validade' => $this->validade($cupom),
            'condicoes' => $this->condicoes($cupom),
            'logoBase64' => $this->logo($tenant?->photo_path),
        ])->setPaper('a4');

        return RodapePdf::aplicar(
            $pdf,
            'Gerado por '.(auth()->user()?->name ?? 'sistema').' em '.Formatar::dataHora(now()),
            ($parceiro ?? 'Todos os parceiros').' · Cupom '.$cupom->codigo,
            30,
        );
    }

    private function desconto(Cupom $cupom): string
    {
        return $cupom->tipo === Cupom::TIPO_PERCENTUAL
            ? rtrim(rtrim(number_format((float) $cupom->valor, 2, ',', '.'), '0'), ',').'% OFF'
            : 'R$ '.number_format((float) $cupom->valor, 2, ',', '.').' OFF';
    }

    private function validade(Cupom $cupom): string
    {
        return 'De '.Formatar::dataHora($cupom->inicio_em).' até '.Formatar::dataHora($cupom->expira_em);
    }

    /**
     * Regras do cupom em linguagem simples, na ordem em que o usuário precisa delas.
     *
     * @return array<int, string>
     */
    private function condicoes(Cupom $cupom): array
    {
        $nomes = collect(Planos::options())->pluck('label', 'value');

        return array_values(array_filter([
            match ($cupom->aplicavel_a) {
                Cupom::APLICAVEL_PLANOS => 'Válido para: '.$cupom->planos->map(fn ($p) => $nomes[(string) $p->cod_plano] ?? $p->cod_plano)->implode(', ').'.',
                Cupom::APLICAVEL_PRIMEIRO_CONTRATO => 'Válido apenas no primeiro contrato do CPF.',
                default => 'Válido para todos os planos.',
            },
            $cupom->limite_uso_por_cpf === 1
                ? 'Uso único por CPF.'
                : "Até {$cupom->limite_uso_por_cpf} usos por CPF.",
            $cupom->limite_uso_total !== null ? "Limitado aos primeiros {$cupom->limite_uso_total} usos." : null,
            (float) $cupom->valor_minimo > 0 ? 'Para planos a partir de R$ '.number_format((float) $cupom->valor_minimo, 2, ',', '.').'.' : null,
            'Não cumulativo com outros cupons.',
        ]));
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
