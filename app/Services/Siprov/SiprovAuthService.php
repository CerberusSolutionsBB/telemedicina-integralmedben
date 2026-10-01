<?php

namespace App\Services\Siprov;

use App\Exceptions\SiprovException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class SiprovAuthService
{
    /**
     * Token único para todo o sistema: usa o store diretamente. Em contexto de
     * tenant o facade Cache aplica tags por tenant, que o store database não suporta.
     */
    private function cache(): Repository
    {
        return Cache::store();
    }

    public function token(): string
    {
        return $this->cache()->remember('siprov_authorization_token', now()->addHours(11), function () {
            try {
                $response = Http::withBasicAuth(
                    config('siprov.user'),
                    config('siprov.password')
                )
                    ->acceptJson()
                    ->post(config('siprov.base_url').'/ext/autenticacao');

                if ($response->failed()) {
                    throw SiprovException::authFailed($response->body());
                }

                $token = $response->json('authorizationToken');

                if (empty($token)) {
                    throw SiprovException::authFailed('Token não retornado pela SIPROV.');
                }

                return $token;
            } catch (Throwable $e) {
                throw SiprovException::authFailed($e->getMessage());
            }
        });
    }

    public function forgetToken(): void
    {
        $this->cache()->forget('siprov_authorization_token');
    }
}
