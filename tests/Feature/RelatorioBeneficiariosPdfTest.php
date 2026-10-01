<?php

namespace Tests\Feature;

use App\Http\Services\Patient\PatientsReportPdfService;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use ReflectionMethod;
use Tests\TestCase;

class RelatorioBeneficiariosPdfTest extends TestCase
{
    public function test_paginacao_primeira_pagina_menor(): void
    {
        $paginar = new ReflectionMethod(PatientsReportPdfService::class, 'paginar');
        $service = app(PatientsReportPdfService::class);

        $tamanhos = array_map('count', $paginar->invoke($service, range(1, 60)));
        $this->assertSame([PatientsReportPdfService::LINHAS_PRIMEIRA_PAGINA, PatientsReportPdfService::LINHAS_POR_PAGINA, 14], $tamanhos);
        $this->assertSame([], $paginar->invoke($service, []));
    }

    public function test_descreve_filtros_aplicados(): void
    {
        $descrever = new ReflectionMethod(PatientsReportPdfService::class, 'descreverFiltros');
        $service = app(PatientsReportPdfService::class);

        $this->assertSame(
            ['Busca: "maria"', 'Status: Inativo', 'Origem: Formulário dinâmico'],
            $descrever->invoke($service, ['search' => ' maria ', 'status' => '0', 'registro' => 'form-dinamico']),
        );
        $this->assertSame(['Status: Ativo'], $descrever->invoke($service, ['status' => '1']));
        $this->assertSame([], $descrever->invoke($service, ['status' => '']));
    }

    public function test_gera_pdf_paginado_com_rodape_em_todas_as_paginas(): void
    {
        $linha = fn (int $i) => [
            'id' => $i, 'nome' => "Beneficiário {$i}", 'cpf' => '123.456.789-09', 'nascimento' => '10/05/1990', 'idade' => 36,
            'sexo' => 'Feminino', 'celular' => '(86) 99431-1316', 'email' => "b{$i}@example.com", 'cod_plano' => '331385',
            'plano' => 'Clínica Familiar', 'origem_key' => 'formulario', 'origem' => 'Formulário', 'ativo' => true, 'cadastro' => '01/10/2026 11:35',
        ];
        $linhas = array_map($linha, range(1, 30));

        $pdf = Pdf::loadView('pdf.relatorio-beneficiarios', [
            'linhas' => $linhas,
            'paginas' => [array_slice($linhas, 0, 18), array_slice($linhas, 18)],
            'resumo' => ['total' => 30, 'ativos' => 30, 'inativos' => 0, 'planos' => [['label' => 'Clínica Familiar', 'total' => 30]], 'origens' => [['label' => 'Formulário', 'total' => 30]]],
            'filtros' => ['Status: Ativo'], 'gerado_por' => 'Ana', 'gerado_em' => '01/10/2026 11:35',
            'tenant' => null, 'parceiro' => 'Parceiro Teste', 'logoBase64' => null,
        ])->setPaper('a4', 'landscape');

        $saida = RodapePdf::aplicar($pdf, 'Gerado por Ana', 'Parceiro Teste')->output();

        $this->assertStringStartsWith('%PDF', $saida);
        $this->assertSame(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }
}
