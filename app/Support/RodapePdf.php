<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;
use Dompdf\Canvas;
use Dompdf\FontMetrics;

/**
 * Rodapé desenhado no canvas do dompdf em todas as páginas: linha divisória,
 * texto à esquerda, texto ao centro e "Página X de Y" à direita.
 * (Nesta versão do dompdf, elementos position:fixed na margem da página não
 * são renderizados, por isso o rodapé não é feito em HTML.)
 */
class RodapePdf
{
    /**
     * @param  float  $margem  margem lateral em pt (32px ≈ 24pt)
     */
    public static function aplicar(PDF $pdf, string $esquerda, string $centro, float $margem = 24): PDF
    {
        $pdf->render();

        $pdf->getDomPDF()->getCanvas()->page_script(
            function (int $pagina, int $total, Canvas $canvas, FontMetrics $fontMetrics) use ($esquerda, $centro, $margem) {
                $fonte = $fontMetrics->getFont('helvetica');
                $tamanho = 7;
                $cor = [0.39, 0.45, 0.55];
                $largura = $canvas->get_width();
                $y = $canvas->get_height() - 28;

                $canvas->line($margem, $y - 7, $largura - $margem, $y - 7, [0.89, 0.91, 0.94], 0.6);

                $canvas->text($margem, $y, $esquerda, $fonte, $tamanho, $cor);

                $larguraCentro = $fontMetrics->getTextWidth($centro, $fonte, $tamanho);
                $canvas->text(($largura - $larguraCentro) / 2, $y, $centro, $fonte, $tamanho, $cor);

                $paginacao = "Página {$pagina} de {$total}";
                $larguraPaginacao = $fontMetrics->getTextWidth($paginacao, $fonte, $tamanho);
                $canvas->text($largura - $margem - $larguraPaginacao, $y, $paginacao, $fonte, $tamanho, $cor);
            }
        );

        return $pdf;
    }
}
