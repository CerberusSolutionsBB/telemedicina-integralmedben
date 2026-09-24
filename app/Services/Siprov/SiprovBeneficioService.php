<?php

namespace App\Services\Siprov;

use App\Data\SiprovBeneficioData;
use App\Exceptions\SiprovException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovBeneficioService
{
    public function __construct(
        private readonly SiprovAuthService $authService,
    ) {}

    public function create(SiprovBeneficioData $data): array
    {
        try {
            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->post(config('siprov.base_url').'/ext/beneficio', $data->toPayload());

            if ($response->unauthorized()) {
                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->post(config('siprov.base_url').'/ext/beneficio', $data->toPayload());
            }

            if ($response->failed()) {
                throw SiprovException::beneficioFailed($response->body());
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            throw SiprovException::beneficioFailed($e->getMessage());
        }
    }

    /**
     * Ativa ou inativa o benefício regravando-o com o campo "ativo".
     * Com codBeneficio o POST atualiza o benefício existente em vez de criar outro.
     */
    public function alterarSituacao(int $codBeneficio, int $codPlano, string $cpfCnpj, bool $ativo): array
    {
        $payload = [
            'ativo'        => $ativo,
            'situacao'     => $ativo ? 'ATIVO' : 'INATIVO',
            'codBeneficio' => $codBeneficio,
            'codLoja'      => (int) config('siprov.cod_loja'),
            'codPlano'     => $codPlano,
            'cpfCnpj'      => preg_replace('/\D/', '', $cpfCnpj),
        ];

        try {
            Log::info('SIPROV | Alterando situação do benefício', [
                'endpoint' => '/ext/beneficio',
                'payload'  => $payload,
            ]);

            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->post(config('siprov.base_url').'/ext/beneficio', $payload);

            if ($response->unauthorized()) {
                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->post(config('siprov.base_url').'/ext/beneficio', $payload);
            }

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao alterar situação do benefício', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                    'payload'  => $payload,
                ]);

                throw SiprovException::beneficioFailed($response->body());
            }

            Log::info('SIPROV | Situação do benefício alterada com sucesso', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->json() ?? [];
        } catch (SiprovException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao alterar situação do benefício', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw SiprovException::beneficioFailed($e->getMessage());
        }
    }
}
