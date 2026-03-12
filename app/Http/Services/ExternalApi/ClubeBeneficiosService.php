<?php

namespace App\Http\Services\ExternalApi;

use App\Interfaces\ExternalApiInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClubeBeneficiosService implements ExternalApiInterface
{
    private const TOKEN_CACHE_KEY = 'rede_parcerias_access_token';
    private const TOKEN_TTL_DAYS  = 364;

    public function __construct(
        private string $baseUrl,
        private string $clientId,
        private string $clientSecret,
    ) {}

    // -------------------------------------------------------------------------
    // ExternalApiInterface
    // -------------------------------------------------------------------------

    /**
     * Cadastra o paciente na RedeParcerias.
     *
     * Campos esperados no payload (normalizados pelo listener):
     *   - nome_completo (ou nome)  → name
     *   - email                    → email
     *   - cpf                      → cpf
     *
     * A senha padrão gerada é o CPF sem formatação.
     * O acesso real é feito via SSO (getSsoToken), não via senha direta.
     */
    public function registerPatient(array $data): array
    {
        // Campos obrigatórios
        // 'nome', 'email', 'cpf' são as chaves geradas pelo role no listener
        $name  = $data['nome'] ?? $data['nome_completo'] ?? $data['name'] ?? null;
        $email = $data['email']                                            ?? null;
        $cpf   = $data['cpf']                                              ?? null;

        if (!$name || !$email || !$cpf) {
            throw new \InvalidArgumentException(
                'RedeParcerias: campos obrigatórios ausentes (nome, email, cpf). Recebido: ' . implode(', ', array_keys($data))
            );
        }

        $cpfClean = preg_replace('/\D/', '', $cpf);

        // Payload obrigatório
        $payload = [
            'name'       => $name,
            'email'      => $email,
            'cpf'        => $cpfClean,
            'password'   => $cpfClean, // senha padrão = CPF sem máscara; acesso real via SSO
            'authorized' => true,
        ];

        // Campos opcionais — incluídos apenas se presentes no payload do paciente
        // 'tel' é a chave gerada pelo role; fallback para slugs comuns
        $cellphone = $data['tel'] ?? $data['whatsapp'] ?? $data['telefone'] ?? $data['celular'] ?? null;
        if ($cellphone) {
            $payload['cellphone'] = $cellphone;
        }

        // 'birth_date' é a chave gerada pelo role; fallback para slugs comuns
        $birthDate = $data['birth_date'] ?? $data['data_de_nascimento'] ?? $data['data_nascimento'] ?? null;
        if ($birthDate) {
            // Normaliza para DD/MM/YYYY caso venha em outro formato
            $payload['birth_date'] = $this->normalizeBirthDate($birthDate);
        }

        // Consentimentos de comunicação (true se o paciente tiver respondido afirmativamente)
        if (isset($data['sms']))        $payload['sms']        = (bool) $data['sms'];
        if (isset($data['newsletter'])) $payload['newsletter'] = (bool) $data['newsletter'];
        if (isset($data['whatsapp']))   $payload['whatsapp']   = (bool) $data['whatsapp'];

        $response = $this->http()->post('/users', $payload);
        $response->throw();

        return $response->json();
    }

    /**
     * Garante que a data de nascimento esteja no formato DD/MM/YYYY.
     */
    private function normalizeBirthDate(string $date): string
    {
        // Já está no formato esperado
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            return $date;
        }

        // Tenta converter de YYYY-MM-DD
        try {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    // -------------------------------------------------------------------------
    // Métodos adicionais
    // -------------------------------------------------------------------------

    /**
     * Gera o link de SSO para o usuário acessar o clube sem digitar senha.
     *
     * @param  string $userId  E-mail ou ID do usuário na RedeParcerias
     * @return string          URL de redirecionamento
     */
    public function getSsoToken(string $userId): string
    {
        $response = $this->http()->get('/sso-token', ['user_id' => $userId]);
        $response->throw();

        return $response->json('redirect');
    }

    /**
     * Autoriza o acesso de um beneficiário (ativa na plataforma).
     *
     * @param  string $value  CPF ou e-mail
     */
    public function authorizeUser(string $value): array
    {
        $response = $this->http()->post('/users/authorize', ['value' => $value]);
        $response->throw();

        return $response->json();
    }

    /**
     * Desautoriza o acesso de um beneficiário.
     *
     * @param  string $value  CPF ou e-mail
     */
    public function unauthorizeUser(string $value): array
    {
        $response = $this->http()->post('/users/unauthorized', ['value' => $value]);
        $response->throw();

        return $response->json();
    }

    /**
     * Lista benefícios disponíveis (cache de 1h).
     */
    public function getOffers(array $filters = []): array
    {
        return Cache::remember(
            'rede_parcerias_offers_' . md5(serialize($filters)),
            now()->addHour(),
            fn () => $this->http()->get('/offers', $filters)->throw()->json()
        );
    }

    // -------------------------------------------------------------------------
    // Autenticação
    // -------------------------------------------------------------------------

    /**
     * Retorna o access token, buscando do cache ou autenticando novamente.
     * O token da RedeParcerias expira em 1 ano — cacheamos por 364 dias.
     */
    private function getToken(): string
    {
        return Cache::remember(
            self::TOKEN_CACHE_KEY,
            now()->addDays(self::TOKEN_TTL_DAYS),
            function () {
                $response = Http::asForm()->post("{$this->baseUrl}/auth", [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

                $response->throw();

                return $response->json('access_token');
            }
        );
    }

    /**
     * Força a renovação do token limpando o cache.
     * Útil se o token for invalidado antes de expirar (ex: ambiente de staging resetado).
     */
    public function forceTokenRefresh(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    /**
     * Retorna uma instância Http já configurada com a base URL e o Bearer token.
     */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->getToken())
            ->acceptJson()
            ->asJson();
    }
}
