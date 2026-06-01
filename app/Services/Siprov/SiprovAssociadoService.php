<?php

namespace App\Services\Siprov;

use App\Data\SiprovAssociadoData;
use App\Data\SiprovAssociadoQueryData;
use App\Exceptions\SiprovException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovAssociadoService
{
    public function __construct(
        private readonly SiprovAuthService $authService,
    ) {}

    public function query(SiprovAssociadoQueryData $data): array
    {
        $params = $data->toQueryParams();

        try {
            Log::info('SIPROV | Consultando associados', [
                'endpoint' => '/ext/associado',
                'params' => $params,
            ]);

            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->get(
                    config('siprov.base_url').'/ext/associado',
                    $params
                );

            if ($response->unauthorized()) {
                Log::warning('SIPROV | Token expirado, renovando token', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->get(
                        config('siprov.base_url').'/ext/associado',
                        $params
                    );
            }

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao consultar associados', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'params' => $params,
                ]);

                throw SiprovException::associadoFailed(
                    $response->body()
                );
            }

            Log::info('SIPROV | Associados consultados com sucesso', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->json() ?? [];

        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao consultar associados', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'params' => $params,
                'trace' => $e->getTraceAsString(),
            ]);

            throw SiprovException::associadoFailed(
                $e->getMessage()
            );
        }
    }

    public function create(SiprovAssociadoData $data): array
    {
        $payload = $data->toPayload();

        try {
            Log::info('SIPROV | Enviando associado', [
                'endpoint' => '/ext/associado',
                'payload' => $payload,
            ]);

            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->post(
                    config('siprov.base_url').'/ext/associado',
                    $payload
                );

            if ($response->unauthorized()) {
                Log::warning('SIPROV | Token expirado, renovando token', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->post(
                        config('siprov.base_url').'/ext/associado',
                        $payload
                    );
            }

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao cadastrar associado', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'payload' => $payload,
                ]);

                throw SiprovException::associadoFailed(
                    $response->body()
                );
            }

            Log::info('SIPROV | Associado cadastrado com sucesso', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->json() ?? [];

        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao integrar associado', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            throw SiprovException::associadoFailed(
                $e->getMessage()
            );
        }
    }
}
