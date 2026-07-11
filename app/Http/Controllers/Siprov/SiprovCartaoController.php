<?php

namespace App\Http\Controllers\Siprov;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class SiprovCartaoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $associado = $request->validate([
            'nomePessoa' => 'required|string',
            'cpfCnpj' => 'required|string',
            'email' => 'nullable|string',
            'telefoneCelular' => 'nullable|string',
            'codPessoa' => 'required|numeric',
            'codBeneficio' => 'required|numeric',
            'planos' => 'required|array',
            'planos.*.codPlano' => 'required|numeric',
            'planos.*.nome' => 'required|string',
            'dataCadastro' => 'nullable|string',
            'dataAdesao' => 'nullable|string',
            'dataAtivacao' => 'nullable|string',
            'situacao' => 'nullable|string',
        ]);

        $cpfFormatado = $this->formatCpf($associado['cpfCnpj']);
        $planoNome = collect($associado['planos'])->pluck('nome')->implode(', ');
        $situacao = 'Ativo';

        $template = File::get(resource_path('views/pdf/siprov-cartao.tex'));

        $replacements = [
            '\\nomeVar' => $this->escapeLatex($associado['nomePessoa']),
            '\\codPessoaVar' => (string) $associado['codPessoa'],
            '\\cpfVar' => $this->escapeLatex($cpfFormatado),
            '\\codBeneficioVar' => (string) $associado['codBeneficio'],
            '\\planoVar' => $this->escapeLatex($planoNome),
            '\\statusVar' => $situacao,
            '\\emailVar' => $this->escapeLatex($associado['email'] ?? '-'),
            '\\telefoneVar' => $this->escapeLatex($associado['telefoneCelular'] ?? '-'),
            '\\cadastroVar' => $this->escapeLatex($associado['dataCadastro'] ?? '-'),
            '\\adesaoVar' => $this->escapeLatex($associado['dataAdesao'] ?? '-'),
            '\\ativacaoVar' => $this->escapeLatex($associado['dataAtivacao'] ?? '-'),
            '\\cpfCnpjVar' => $this->escapeLatex($associado['cpfCnpj']),
        ];

        $texContent = str_replace(array_keys($replacements), array_values($replacements), $template);

        $tempDir = config('latex.temp_dir');
        File::makeDirectory($tempDir, 0755, true, true);

        $filename = 'cartao-' . $associado['codPessoa'];
        $texFile = $tempDir . '/' . $filename . '.tex';
        $pdfFile = $tempDir . '/' . $filename . '.pdf';

        File::put($texFile, $texContent);

        $command = sprintf(
            'cd %s && %s %s -output-directory=%s %s 2>&1',
            escapeshellarg($tempDir),
            config('latex.bin'),
            config('latex.options'),
            escapeshellarg($tempDir),
            escapeshellarg($texFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || ! File::exists($pdfFile)) {
            File::delete([$texFile, $pdfFile]);

            return back()->withErrors([
                'pdf' => 'Erro ao gerar cartão PDF. Detalhes: ' . implode("\n", array_slice($output, -10)),
            ]);
        }

        $pdfContent = File::get($pdfFile);

        File::delete([
            $texFile,
            $pdfFile,
            $tempDir . '/' . $filename . '.aux',
            $tempDir . '/' . $filename . '.log',
        ]);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }

    private function formatCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }

        if (strlen($cpf) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cpf);
        }

        return $cpf;
    }

    private function escapeLatex(string $text): string
    {
        $specialChars = [
            '\\' => '\\textbackslash{}',
            '&' => '\\&',
            '%' => '\\%',
            '$' => '\\$',
            '#' => '\\#',
            '_' => '\\_',
            '{' => '\\{',
            '}' => '\\}',
            '~' => '\\textasciitilde{}',
            '^' => '\\textasciicircum{}',
        ];

        $text = str_replace(array_keys($specialChars), array_values($specialChars), $text);

        $text = str_replace([
            'á', 'à', 'ã', 'â', 'ä',
            'é', 'è', 'ê', 'ë',
            'í', 'ì', 'î', 'ï',
            'ó', 'ò', 'õ', 'ô', 'ö',
            'ú', 'ù', 'û', 'ü',
            'ç', 'ñ',
            'Á', 'À', 'Ã', 'Â', 'Ä',
            'É', 'È', 'Ê', 'Ë',
            'Í', 'Ì', 'Î', 'Ï',
            'Ó', 'Ò', 'Õ', 'Ô', 'Ö',
            'Ú', 'Ù', 'Û', 'Ü',
            'Ç', 'Ñ',
        ], [
            '{\\' . "a}", '{\\' . "a}", '{\\' . "a}", '{\\' . "a}", '{\\' . "a}",
            '{\\' . "e}", '{\\' . "e}", '{\\' . "e}", '{\\' . "e}",
            '{\\' . "i}", '{\\' . "i}", '{\\' . "i}", '{\\' . "i}",
            '{\\' . "o}", '{\\' . "o}", '{\\' . "o}", '{\\' . "o}", '{\\' . "o}",
            '{\\' . "u}", '{\\' . "u}", '{\\' . "u}", '{\\' . "u}",
            '{\\' . "c}", '{\\' . "n}",
            '{\\' . "A}", '{\\' . "A}", '{\\' . "A}", '{\\' . "A}", '{\\' . "A}",
            '{\\' . "E}", '{\\' . "E}", '{\\' . "E}", '{\\' . "E}",
            '{\\' . "I}", '{\\' . "I}", '{\\' . "I}", '{\\' . "I}",
            '{\\' . "O}", '{\\' . "O}", '{\\' . "O}", '{\\' . "O}", '{\\' . "O}",
            '{\\' . "U}", '{\\' . "U}", '{\\' . "U}", '{\\' . "U}",
            '{\\' . "C}", '{\\' . "N}",
        ], $text);

        return $text;
    }
}
