<?php
namespace App\Services;

use App\Models\CredenciasCluble;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClubleBeneficiarioService
{
    protected string $baseUrl = 'https://api.staging.clubeparcerias.com.br/api-client/v1';

    /**
     * Cadastra um novo beneficiário na API do Clube
     */
    public function cadastrarBeneficiario(array $dados, ?CredenciasCluble $credencial = null): ?array
    {
        // Se não passou credencial, pega a primeira ativa
        $credencial = $credencial ?? $this->getCredencialAtiva();

        if (! $credencial || $credencial->isTokenExpired()) {
            Log::error('Nenhuma credencial ativa disponível para cadastrar beneficiário');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => $credencial->getAuthorizationHeader(),
            ])->post("{$this->baseUrl}/users", $this->formatarDados($dados));

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                    'status'  => $response->status(),
                ];
            }

            // Log do erro
            Log::error('Falha ao cadastrar beneficiário na API Clube', [
                'status'         => $response->status(),
                'body'           => $response->body(),
                'dados_enviados' => $dados,
            ]);

            return [
                'success' => false,
                'error'   => $response->json(),
                'status'  => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao cadastrar beneficiário', [
                'message' => $e->getMessage(),
                'dados'   => $dados,
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Formata os dados para envio à API
     */
    private function formatarDados(array $dados): array
    {
        $formatado = [
            'name'     => $dados['name'],
            'email'    => $dados['email'],
            'cpf'      => $this->limparCpf($dados['cpf']),
            'password' => $dados['password'] ?? $this->gerarSenhaTemporaria(),
        ];

        // Campos opcionais
        if (! empty($dados['birth_date'])) {
            $formatado['birth_date'] = $dados['birth_date'];
        }

        if (isset($dados['newsletter'])) {
            $formatado['newsletter'] = (bool) $dados['newsletter'];
        }

        if (isset($dados['sms'])) {
            $formatado['sms'] = (bool) $dados['sms'];
        }

        if (isset($dados['whatsapp'])) {
            $formatado['whatsapp'] = (bool) $dados['whatsapp'];
        }

        if (isset($dados['authorized'])) {
            $formatado['authorized'] = (bool) $dados['authorized'];
        }

        if (! empty($dados['cellphone'])) {
            $formatado['cellphone'] = $dados['cellphone'];
        }

        if (! empty($dados['expiration_date'])) {
            $formatado['expiration_date'] = $dados['expiration_date'];
        }

        if (! empty($dados['company_name'])) {
            $formatado['company_name'] = $dados['company_name'];
        }

        return $formatado;
    }

    /**
     * Limpa o CPF (remove pontos e traço)
     */
    private function limparCpf(?string $cpf): ?string
    {
        if (! $cpf) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $cpf);
    }

    /**
     * Gera senha temporária
     */
    private function gerarSenhaTemporaria(): string
    {
        return bin2hex(random_bytes(8)); // 16 caracteres
    }

    /**
     * Busca credencial ativa (token não expirado)
     */
    private function getCredencialAtiva(): ?CredenciasCluble
    {
        return CredenciasCluble::where('token_expires_at', '>', now())
            ->orWhereNull('token_expires_at')
            ->latest()
            ->first();
    }
}
