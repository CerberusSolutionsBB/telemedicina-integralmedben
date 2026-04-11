<?php
namespace App\Services;

use App\Models\CredenciasCluble;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClubleAuthService
{
    protected string $baseUrl = 'https://api.staging.clubeparcerias.com.br/api-client/v1';

    /**
     * Autentica e obtém token da API Clube
     */
    public function authenticate(string $clientId, string $clientSecret, string $scope = '*', string $grantType = 'client_credentials'): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/auth", [
                'grant_type'    => $grantType,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => $scope,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Falha na autenticação Clube', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao autenticar na API Clube', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cria credencial e já autentica na API
     */
    public function createAndAuthenticate(array $data): ?CredenciasCluble
    {
        // Faz autenticação na API
        $authResponse = $this->authenticate(
            $data['client_id'],
            $data['client_secret'],
            $data['scope'] ?? '*',
            $data['grant_type'] ?? 'client_credentials'
        );

        if (! $authResponse) {
            return null;
        }

        // Calcula data de expiração
        $expiresAt = now()->addSeconds($authResponse['expires_in']);

        // Cria registro no banco com todos os dados
        return CredenciasCluble::create([
            'user_id'          => $data['user_id'] ?? null,
            'title'            => $data['title'] ?? null,
            'grant_type'       => $data['grant_type'] ?? 'client_credentials',
            'client_id'        => $data['client_id'],
            'client_secret'    => $data['client_secret'],
            'scope'            => $data['scope'] ?? '*',
            'token_type'       => $authResponse['token_type'],
            'expires_in'       => $authResponse['expires_in'],
            'access_token'     => $authResponse['access_token'],
            'token_expires_at' => $expiresAt,
        ]);
    }

    /**
     * Renova token de uma credencial existente
     */
    public function refreshToken(CredenciasCluble $credencial): ?CredenciasCluble
    {
        return $this->createAndAuthenticate([
            'user_id'       => $credencial->user_id,
            'title'         => $credencial->title,
            'grant_type'    => $credencial->grant_type,
            'client_id'     => $credencial->client_id,
            'client_secret' => $credencial->client_secret,
            'scope'         => $credencial->scope,
        ]);
    }
}
