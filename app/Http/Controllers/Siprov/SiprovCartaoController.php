<?php
namespace App\Http\Controllers\Siprov;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class SiprovCartaoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $associado = $request->validate([
            'nomePessoa'        => 'required|string',
            'cpfCnpj'           => 'required|string',
            'email'             => 'nullable|string',
            'telefoneCelular'   => 'nullable|string',
            'codPessoa'         => 'required|numeric',
            'codBeneficio'      => 'required|numeric',
            'planos'            => 'required|array',
            'planos.*.codPlano' => 'required|numeric',
            'planos.*.nome'     => 'required|string',
            'dataCadastro'      => 'nullable|string',
            'dataAdesao'        => 'nullable|string',
            'dataAtivacao'      => 'nullable|string',
            'situacao'          => 'nullable|string',
        ]);

        $cpfFormatado = $this->formatCpf($associado['cpfCnpj']);
        $planoNome    = collect($associado['planos'])->pluck('nome')->implode(', ');
        $filename     = 'cartao-' . $associado['codPessoa'];

        $qr = 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::size(240)->margin(0)->generate((string) $associado['codPessoa'])
        );

        $dados = [
            'nome'          => $associado['nomePessoa'],
            'cpf'           => $cpfFormatado,
            'codigo'        => $associado['codPessoa'],
            'plano'         => $planoNome,
            'emissao'       => now()->format('d/m/Y'),
            'telefone'      => '(91) 4040-0700',
            'qr'            => $qr,
            'logo'          => $this->imageBase64('images/logo_cartao.png'),
            'logo_vertical' => $this->imageBase64('images/Code_Generated_Image.png'),
            'fundo_frente'  => $this->imageBase64('images/cartao-frente.png'),
            'fundo_verso'   => $this->imageBase64('images/cartao-fundo.png'),
        ];

        $pdf = Pdf::loadView('pdf.siprov-cartao', $dados);

        Log::info('SIPROV Cartao | PDF gerado com sucesso', [
            'codPessoa' => $associado['codPessoa'],
        ]);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }

    private function imageBase64(string $relativePath): string
    {
        $path = public_path($relativePath);

        return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
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
}