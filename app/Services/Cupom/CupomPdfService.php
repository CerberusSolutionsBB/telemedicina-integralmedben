<?php

namespace App\Services\Cupom;

use App\Models\Cupom;
use App\Models\Tenant;
use App\Support\Formatar;
use App\Support\Planos;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Voucher do cupom em PDF (para imprimir ou entregar ao cliente): código em destaque,
 * desconto, onde vale, validade, condições e como usar. Só informação para o cliente:
 * controles internos (ex.: limite total de usos) não aparecem.
 */
class CupomPdfService
{
    public function gerar(Cupom $cupom): DomPdf
    {
        $dados = $this->dados($cupom);
        $parceiro = $dados['parceiro'];

        $pdf = Pdf::loadView('pdf.cupom', $dados)->setPaper('a4');

        return RodapePdf::aplicar(
            $pdf,
            'Gerado por '.(auth()->user()?->name ?? 'sistema').' em '.Formatar::dataHora(now()),
            ($parceiro ?? 'Todos os parceiros').' · Cupom '.$cupom->codigo,
            30,
        );
    }

    /**
     * Conteúdo do voucher (o que o cliente vê).
     *
     * @return array<string, mixed> variáveis de pdf/cupom.blade.php (+ parceiro, para o rodapé)
     */
    public function dados(Cupom $cupom): array
    {
        $cupom->loadMissing(['planos', 'tenant.details']);
        $tenant = $cupom->tenant;
        $parceiro = $tenant ? ($tenant->name ?: ($tenant->details->first()?->descricao ?? $tenant->id)) : null;
        $situacao = $cupom->situacao();

        return [
            'parceiro' => $parceiro,
            'rotulo' => $parceiro ?? 'Válido em todos os parceiros',
            'titulo' => 'Cupom de desconto',
            'codigo' => $cupom->codigo,
            'aviso' => in_array($situacao, [Cupom::STATUS_ATIVO, 'agendado'], true) ? null : "Atenção: este cupom está {$situacao} e não pode ser usado.",
            'comoUsar' => [
                'Informe o código :codigo no campo "Cupom" do cadastro ou do formulário de adesão.',
                'Clique em "Aplicar" para conferir o desconto antes de enviar.',
                'O desconto é aplicado sobre o valor do plano escolhido.',
            ],
            'desconto' => $this->desconto($cupom),
            // Texto definido por quem criou o cupom (ex.: "na consulta com nutricionista").
            'textoDesconto' => $cupom->texto_desconto ?: Cupom::TEXTO_DESCONTO_PADRAO,
            'validade' => $this->validade($cupom),
            'condicoes' => $this->condicoes($cupom),
            'logoBase64' => $this->logo($tenant),
        ];
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
     * Regras do cupom em linguagem simples, na ordem em que o cliente precisa delas.
     * O limite total de usos é controle interno do parceiro e não é impresso.
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
            (float) $cupom->valor_minimo > 0 ? 'Para planos a partir de R$ '.number_format((float) $cupom->valor_minimo, 2, ',', '.').'.' : null,
            'Não cumulativo com outros cupons.',
        ]));
    }

    private function logo(?Tenant $tenant): ?string
    {
        if (! $tenant) {
            return null;
        }

        // Logomarca da página do parceiro (Página › configuração › logo).
        $caminho = $tenant->caminhoArquivoLogo();

        // Compatibilidade: parceiros antigos usavam a foto do cadastro (photo_path).
        if (! $caminho && $tenant->photo_path) {
            $caminho = base_path('storage/app/public/'.$tenant->photo_path);
        }

        return $caminho && is_file($caminho)
            ? 'data:'.mime_content_type($caminho).';base64,'.base64_encode(file_get_contents($caminho))
            : null;
    }
}
