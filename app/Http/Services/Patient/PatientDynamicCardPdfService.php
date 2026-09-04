<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;
use App\Models\Siprov;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PatientDynamicCardPdfService
{
    private const COR_PRIMARIA_PADRAO = '#22d3ee';

    private const COR_SECUNDARIA_PADRAO = '#0e7490';

    private const COR_TEXTO_PADRAO = '#ffffff';

    private const FONTE_PADRAO = 'sans-serif';

    private const FONTES_CSS = [
        'sans-serif' => 'Helvetica, Arial, sans-serif',
        'serif' => "'Times New Roman', Times, serif",
        'monospace' => "'Courier New', Courier, monospace",
    ];

    public function execute(Patient $patient): string
    {
        $detail = TenantsDetail::where('tenant_id', tenant('id'))->first();

        $cpfFormatado = $this->formatCpf($patient->cpf);
        $planoNome = $this->resolvePlano($patient);

        $qr = 'data:image/svg+xml;base64,'.base64_encode(
            QrCode::size(240)->margin(0)->generate(config('services.qrcode.link'))
        );

        $dados = [
            'nome' => $patient->nome,
            'cpf' => $cpfFormatado,
            'plano' => $planoNome,
            'qr' => $qr,
            'cor_primaria' => $detail?->cartao_cor_primaria ?: self::COR_PRIMARIA_PADRAO,
            'cor_secundaria' => $detail?->cartao_cor_secundaria ?: self::COR_SECUNDARIA_PADRAO,
            'cor_texto' => $detail?->cartao_cor_texto ?: self::COR_TEXTO_PADRAO,
            'fonte_css' => self::FONTES_CSS[$detail?->cartao_fonte] ?? self::FONTES_CSS[self::FONTE_PADRAO],
            'logo' => $this->resolveImagemBase64($detail?->cartao_logo),
            'fundo_frente' => $this->resolveImagemBase64($detail?->cartao_imagem_frente),
            'fundo_verso' => $this->resolveImagemBase64($detail?->cartao_imagem_verso),
        ];

        $pdf = Pdf::loadView('pdf.cartao-dinamico', $dados);

        Log::info('Paciente Cartao Dinamico | PDF gerado com sucesso', [
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

    private function resolveImagemBase64(?string $fileName): ?string
    {
        if (! $fileName) {
            return null;
        }

        $tenant = tenant();
        $path = $tenant->resolveCartaoAssetPath($fileName);

        if (! $path) {
            return null;
        }

        $mime = Storage::disk('tenants')->mimeType($path);
        $conteudo = Storage::disk('tenants')->get($path);

        return 'data:'.$mime.';base64,'.base64_encode($conteudo);
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
