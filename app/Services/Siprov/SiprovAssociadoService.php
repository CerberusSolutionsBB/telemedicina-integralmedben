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

        Log::info('SIPROV | data', [
            'data' => $data,
        ]);

        Log::info('SIPROV | params', [
            'params' => $params,
        ]);

        try {
            Log::info('SIPROV | Consultando associados', [
                'endpoint' => '/ext/associado',
                'params'   => $params,
            ]);

            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->get(
                    config('siprov.base_url') . '/ext/associado',
                    $params
                );

            if ($response->unauthorized()) {
                Log::warning('SIPROV | Token expirado, renovando token', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);

                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->get(
                        config('siprov.base_url') . '/ext/associado',
                        $params
                    );
            }

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao consultar associados', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                    'params'   => $params,
                ]);

                throw SiprovException::associadoFailed(
                    $response->body()
                );
            }

            Log::info('SIPROV | Associados consultados com sucesso', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->json() ?? [];

        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao consultar associados', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'params'  => $params,
                'trace'   => $e->getTraceAsString(),
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
                'payload'  => $payload,
            ]);

            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->post(
                    config('siprov.base_url') . '/ext/associado',
                    $payload
                );

            if ($response->unauthorized()) {
                Log::warning('SIPROV | Token expirado, renovando token', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);

                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->post(
                        config('siprov.base_url') . '/ext/associado',
                        $payload
                    );
            }

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao cadastrar associado', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                    'payload'  => $payload,
                ]);

                throw SiprovException::associadoFailed(
                    $response->body()
                );
            }

            Log::info('SIPROV | Associado cadastrado com sucesso', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->json() ?? [];

        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao integrar associado', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'payload' => $payload,
                'trace'   => $e->getTraceAsString(),
            ]);

            throw SiprovException::associadoFailed(
                $e->getMessage()
            );
        }
    }

    public function cancelarBeneficio(int $codBeneficio): array
    {
        try {
            Log::info('SIPROV | Cancelando benefício', [
                'endpoint' => '/ext/beneficio/' . $codBeneficio . '/cancelar',
            ]);

            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->put(
                    config('siprov.base_url') . '/ext/beneficio/' . $codBeneficio . '/cancelar'
                );

            if ($response->unauthorized()) {
                Log::warning('SIPROV | Token expirado, renovando token', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);

                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->put(
                        config('siprov.base_url') . '/ext/beneficio/' . $codBeneficio . '/cancelar'
                    );
            }

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao cancelar benefício', [
                    'status'        => $response->status(),
                    'response'      => $response->body(),
                    'cod_beneficio' => $codBeneficio,
                ]);

                throw SiprovException::cancelarBeneficioFailed(
                    $response->body()
                );
            }

            Log::info('SIPROV | Benefício cancelado com sucesso', [
                'status'        => $response->status(),
                'cod_beneficio' => $codBeneficio,
            ]);

            return $response->json() ?? [];

        } catch (SiprovException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao cancelar benefício', [
                'message'       => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'cod_beneficio' => $codBeneficio,
                'trace'         => $e->getTraceAsString(),
            ]);

            throw SiprovException::cancelarBeneficioFailed(
                $e->getMessage()
            );
        }
    }
}
