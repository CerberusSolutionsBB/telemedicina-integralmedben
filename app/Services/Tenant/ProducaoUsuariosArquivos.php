<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Arquivos do relatório de produção por usuário: PDF (dompdf) e XLSX (duas abas:
 * Resumo e Registros).
 */
class ProducaoUsuariosArquivos
{
    /** Linhas da lista detalhada por página do PDF (paisagem). */
    private const LINHAS_POR_PAGINA = 30;

    public function pdf(string $tenantId, array $dados): string
    {
        $tenant = Tenant::find($tenantId);
        $parceiro = $tenant?->name ?: ($tenant?->details()->first()?->descricao ?? $tenantId);

        $pdf = Pdf::loadView('pdf.relatorio-producao', [
            ...$dados,
            'parceiro' => $parceiro,
            'paginas' => array_chunk($dados['registros'], self::LINHAS_POR_PAGINA),
        ])->setPaper('a4', 'landscape');

        return RodapePdf::aplicar(
            $pdf,
            'Gerado por '.($dados['gerado_por'] ?? 'sistema').' em '.$dados['gerado_em'],
            $parceiro.' · Produção por usuário',
        )->output();
    }

    /**
     * @return string conteúdo do .xlsx
     */
    public function xlsx(array $dados): string
    {
        $planos = $dados['planosColunas'];
        $planilha = new Spreadsheet;

        $resumo = $planilha->getActiveSheet()->setTitle('Resumo');
        $linhas = [
            ['Produção por usuário'],
            ["Período: {$dados['periodo']['de']} a {$dados['periodo']['ate']}"],
            [$dados['filtros'] ? 'Filtros: '.implode(' · ', $dados['filtros']) : 'Sem filtros'],
            ['Total de registros', $dados['total']],
            [],
        ];
        $titulos = [1];

        $linhas[] = ['Por usuário', 'Perfis', 'Total', '%', ...array_values($planos)];
        $titulos[] = count($linhas);
        foreach ($dados['usuarios'] as $u) {
            $linhas[] = [$u['usuario'], $u['perfis'], $u['total'], $u['percentual'] / 100, ...array_values(array_map(fn ($c) => $u['planos'][$c] ?? 0, array_keys($planos)))];
        }

        foreach (['Por plano' => $dados['planos'], 'Por origem' => $dados['origens']] as $titulo => $itens) {
            $linhas[] = [];
            $linhas[] = [$titulo, 'Total', '%'];
            $titulos[] = count($linhas);
            foreach ($itens as $item) {
                $linhas[] = [$item['label'], $item['total'], $item['percentual'] / 100];
            }
        }

        $linhas[] = [];
        $linhas[] = [$dados['evolucao']['tipo'] === 'dia' ? 'Por dia' : 'Por semana', 'Total'];
        $titulos[] = count($linhas);
        foreach ($dados['evolucao']['itens'] as $item) {
            $linhas[] = [$item['label'], $item['total']];
        }

        // Comparação estrita: sem ela o PhpSpreadsheet grava 0 como célula vazia.
        $resumo->fromArray($linhas, null, 'A1', true);
        $this->negrito($resumo, $titulos);
        $this->percentuais($resumo, $linhas);

        $detalhe = $planilha->createSheet()->setTitle('Registros');
        $detalhe->fromArray([
            ['Data', 'Usuário', 'Perfis', 'Beneficiário', 'Plano', 'Origem'],
            ...array_map(fn ($r) => [$r['data'], $r['usuario'], $r['perfis'], $r['paciente'], $r['plano'], $r['origem']], $dados['registros']),
        ], null, 'A1', true);
        $this->negrito($detalhe, [1]);

        foreach ([$resumo, $detalhe] as $aba) {
            foreach (range('A', 'H') as $coluna) {
                $aba->getColumnDimension($coluna)->setAutoSize(true);
            }
        }

        ob_start();
        (new Xlsx($planilha))->save('php://output');

        return ob_get_clean();
    }

    private function negrito(Worksheet $aba, array $linhas): void
    {
        foreach ($linhas as $linha) {
            $aba->getStyle("{$linha}:{$linha}")->getFont()->setBold(true);
        }
    }

    /**
     * Coluna "%" como porcentagem (os valores foram gravados como fração).
     */
    private function percentuais(Worksheet $aba, array $linhas): void
    {
        $colunaPct = null;

        foreach ($linhas as $i => $linha) {
            if (in_array('%', $linha, true)) {
                $colunaPct = chr(ord('A') + array_search('%', $linha, true));

                continue;
            }
            if ($linha === []) {
                $colunaPct = null;

                continue;
            }
            if ($colunaPct) {
                $aba->getStyle($colunaPct.($i + 1))->getNumberFormat()->setFormatCode('0.0%');
            }
        }
    }
}
