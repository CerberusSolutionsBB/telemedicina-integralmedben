<?php
namespace App\Services\Siprov;

use App\Data\SiprovBeneficioData;
use App\Exceptions\SiprovException;
use Illuminate\Support\Facades\Http;
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
                ->post(config('siprov.base_url') . '/ext/beneficio', $data->toPayload());

            if ($response->unauthorized()) {
                $this->authService->forgetToken();

                $response = Http::withToken($this->authService->token())
                    ->acceptJson()
                    ->post(config('siprov.base_url') . '/ext/beneficio', $data->toPayload());
            }

            if ($response->failed()) {
                throw SiprovException::beneficioFailed($response->body());
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            throw SiprovException::beneficioFailed($e->getMessage());
        }
    }
}