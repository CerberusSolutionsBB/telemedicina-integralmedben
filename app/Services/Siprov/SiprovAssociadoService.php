<?php
namespace App\Services\Siprov;

use App\Data\SiprovAssociadoData;
use App\Exceptions\SiprovException;
use Illuminate\Support\Facades\Http;
use Throwable;

class SiprovAssociadoService
{
    public function __construct(
        private readonly SiprovAuthService $authService,
    ) {}

    public function create(SiprovAssociadoData $data): array
    {
        try {
            $response = Http::withToken($this->authService->token())
                ->acceptJson()
                ->post(config('siprov.base_url') . '/ext/associado', $data->toPayload());

            if ($response->unauthorized()) {
                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->post(config('siprov.base_url') . '/ext/associado', $data->toPayload());
            }

            if ($response->failed()) {
                throw SiprovException::associadoFailed($response->body());
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            throw SiprovException::associadoFailed($e->getMessage());
        }
    }
}