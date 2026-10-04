<?php

namespace App\Http\Services\Patient;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Planilha Excel pronta para preencher e importar beneficiários: aba de dados
 * só com o cabeçalho (formatos e listas já configurados) e aba de instruções.
 */
class ModeloImportacaoPacientesService
{
    /** Linhas já formatadas/validadas para preenchimento. */
    private const LINHAS = 1000;

    private const COR_CABECALHO = '0F7A85';

    /**
     * Colunas fixas: título => [obrigatória, largura, formato, instrução].
     * Os títulos são os mesmos lidos por ImportPatientsService.
     */
    private const COLUNAS = [
        'Nome' => [true, 32, 'texto', 'Nome completo do beneficiário.'],
        'Data de Nascimento' => [true, 20, 'data', 'Data no formato dd/mm/aaaa (ex.: 25/03/1990).'],
        'CPF' => [false, 18, 'texto', 'Com ou sem pontuação (ex.: 123.456.789-09). CPF já cadastrado é ignorado.'],
        'RG' => [false, 16, 'texto', 'Opcional.'],
        'Sexo' => [false, 14, 'lista:Masculino,Feminino', 'Escolha na lista: Masculino ou Feminino.'],
        'Email' => [false, 30, 'texto', 'Opcional. Precisa ser um e-mail válido.'],
        'Telefone' => [false, 18, 'texto', 'Com DDD (ex.: (11) 99999-0000).'],
        'Status' => [false, 12, 'lista:Ativo,Inativo', 'Ativo ou Inativo. Em branco = Ativo.'],
    ];

    public function gerar($questions): StreamedResponse
    {
        $planilha = new Spreadsheet;
        $planilha->getProperties()->setTitle('Modelo de importação de beneficiários');

        $dados = $planilha->getActiveSheet();
        $dados->setTitle('Beneficiários');
        $this->montarDados($dados, $questions);

        $this->montarInstrucoes($planilha->createSheet(), $questions);

        $planilha->setActiveSheetIndex(0);

        $resposta = new StreamedResponse(fn () => (new Xlsx($planilha))->save('php://output'));
        $resposta->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $resposta->headers->set('Content-Disposition', 'attachment; filename="modelo-importacao-beneficiarios.xlsx"');
        $resposta->headers->set('Cache-Control', 'no-store');

        return $resposta;
    }

    private function montarDados(Worksheet $sheet, $questions): void
    {
        $colunas = self::COLUNAS;
        foreach ($questions as $question) {
            $colunas[$question->title] ??= [false, max(18, min(40, mb_strlen($question->title) + 4)), 'texto', 'Pergunta do formulário (opcional).'];
        }

        $ultimaLinha = self::LINHAS + 1;
        $indice = 1;

        foreach ($colunas as $titulo => [$obrigatoria, $largura, $formato, $instrucao]) {
            $letra = Coordinate::stringFromColumnIndex($indice++);
            $intervalo = "{$letra}2:{$letra}{$ultimaLinha}";

            $sheet->setCellValue("{$letra}1", $obrigatoria ? "{$titulo} *" : $titulo);
            $sheet->getColumnDimension($letra)->setWidth($largura);
            $sheet->getComment("{$letra}1")->getText()->createTextRun($instrucao);

            if ($formato === 'texto') {
                // Texto: CPF/telefone não perdem zeros à esquerda nem viram notação científica.
                $sheet->getStyle($intervalo)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            } elseif ($formato === 'data') {
                $sheet->getStyle($intervalo)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $this->validar($sheet, "{$letra}2", $intervalo, DataValidation::TYPE_DATE, $instrucao, operador: DataValidation::OPERATOR_BETWEEN, f1: 'DATE(1900,1,1)', f2: 'TODAY()');
            } elseif (str_starts_with($formato, 'lista:')) {
                $this->validar($sheet, "{$letra}2", $intervalo, DataValidation::TYPE_LIST, $instrucao, f1: '"'.substr($formato, 6).'"');
            }
        }

        $ultima = Coordinate::stringFromColumnIndex($indice - 1);
        $cabecalho = $sheet->getStyle("A1:{$ultima}1");
        $cabecalho->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $cabecalho->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COR_CABECALHO);
        $cabecalho->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->freezePane('A2');
        $sheet->setSelectedCell('A2');
    }

    private function validar(Worksheet $sheet, string $celula, string $intervalo, string $tipo, string $mensagem, ?string $operador = null, ?string $f1 = null, ?string $f2 = null): void
    {
        $validacao = $sheet->getCell($celula)->getDataValidation();
        $validacao->setType($tipo)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Valor inválido')
            ->setError($mensagem)
            ->setPromptTitle('Como preencher')
            ->setPrompt($mensagem)
            ->setFormula1($f1);

        if ($operador) {
            $validacao->setOperator($operador)->setFormula2($f2);
        }

        $validacao->setSqref($intervalo);
    }

    private function montarInstrucoes(Worksheet $sheet, $questions): void
    {
        $sheet->setTitle('Instruções');

        $linhas = [
            ['Como importar beneficiários'],
            [''],
            ['1. Preencha a aba "Beneficiários": um beneficiário por linha, a partir da linha 2.'],
            ['2. Não altere nem apague a linha de títulos (linha 1).'],
            ['3. Colunas com * são obrigatórias: Nome e Data de Nascimento.'],
            ['4. Salve o arquivo em Excel (.xlsx) e envie em "Importar" na tela de Beneficiários.'],
            ['5. Linhas com erro são ignoradas e listadas ao final da importação; as demais são importadas.'],
            [''],
            ['Coluna', 'Obrigatória', 'Como preencher', 'Exemplo'],
        ];

        $exemplos = [
            'Nome' => 'Maria da Silva',
            'Data de Nascimento' => '25/03/1990',
            'CPF' => '123.456.789-09',
            'RG' => '12.345.678-9',
            'Sexo' => 'Feminino',
            'Email' => 'maria@exemplo.com',
            'Telefone' => '(11) 99999-0000',
            'Status' => 'Ativo',
        ];

        foreach (self::COLUNAS as $titulo => [$obrigatoria, , , $instrucao]) {
            $linhas[] = [$titulo, $obrigatoria ? 'Sim' : 'Não', $instrucao, $exemplos[$titulo] ?? ''];
        }
        foreach ($questions as $question) {
            if (! isset(self::COLUNAS[$question->title])) {
                $linhas[] = [$question->title, 'Não', 'Pergunta do formulário.', ''];
            }
        }

        $sheet->fromArray($linhas, null, 'A1', true);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB(self::COR_CABECALHO);
        $titulos = $sheet->getStyle('A9:D9');
        $titulos->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $titulos->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COR_CABECALHO);

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(70);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getStyle('C10:C'.count($linhas))->getAlignment()->setWrapText(true);
    }
}
