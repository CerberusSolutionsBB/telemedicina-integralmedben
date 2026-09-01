<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;
use App\Models\Siprov;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PatientCardPdfService
{
    public function execute(Patient $patient): string
    {
        $cpfFormatado = $this->formatCpf($patient->cpf);
        $planoNome    = $this->resolvePlano($patient);

        $qr = 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::size(240)->margin(0)->generate(config('services.qrcode.link'))
        );

        $dados = [
            'nome'          => $patient->nome,
            'cpf'           => $cpfFormatado,
            'codigo'        => $patient->id,
            'plano'         => $planoNome,
            'emissao'       => now()->format('d/m/Y'),
            'telefone'      => '(91) 4040-0700',
            'qr'            => $qr,
            'logo'          => $this->imageBase64('images/logo_cartao.png'),
            'logo_vertical' => $this->imageBase64('images/Code_Generated_Image.png'),
            'fundo_frente'  => $this->imageBase64('images/cartao-frente.png'),
            'fundo_verso'   => $this->imageBase64('images/cartao-fundo.png'),
        ];

        $pdf = Pdf::loadView('pdf.paciente-cartao', $dados);

        Log::info('Paciente Cartao | PDF gerado com sucesso', [
            'patientId' => $patient->id,
        ]);

        return $pdf->output();
    }

    private function resolvePlano(Patient $patient): string
    {
        $cpf = preg_replace('/\D/', '', (string) $patient->cpf);

        if ($cpf) {
            $labels = TelemedicinaTenant::where('tenant_id', tenant('id'))
                ->whereNotNull('data->cpf_cnpj')
                ->get()
                ->filter(fn (TelemedicinaTenant $item) => preg_replace('/\D/', '', (string) ($item->data['cpf_cnpj'] ?? '')) === $cpf)
                ->pluck('data.plano_label')
                ->map(fn ($label) => trim((string) $label))
                ->filter()
                ->unique()
                ->values();

            if ($labels->isNotEmpty()) {
                return $this->normalizePlanoNome($labels->implode(', '));
            }

            $siprov = Siprov::on('mysql')
                ->where('cpf_cnpj', $cpf)
                ->where('ativo', true)
                ->orderByDesc('updated_at')
                ->first();

            if ($siprov) {
                return $this->normalizePlanoNome($siprov->planoLabel);
            }
        }

        $tenant = Tenant::find(tenant('id'));

        return $tenant?->details->first()?->descricao
            ?: ($tenant?->name ?: 'Plano não identificado');
    }

    private function normalizePlanoNome(string $planoNome): string
    {
        return preg_replace('/MEDBEM/i', 'MEDBEN', $planoNome);
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
