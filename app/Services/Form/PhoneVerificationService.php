<?php

namespace App\Services\Form;

use App\Services\SmsSenderService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Verificação de telefone por código SMS nos formulários públicos.
 *
 * Fluxo: sendCode() → o usuário digita o código → verifyCode() devolve um token
 * → o envio do formulário só é aceito com consumeToken() válido para o número.
 */
class PhoneVerificationService
{
    public const CODE_TTL_MINUTES = 10;

    public const TOKEN_TTL_MINUTES = 30;

    public const RESEND_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    // Máximo de códigos por número por hora (protege a cota de SMS)
    public const MAX_SENDS_PER_HOUR = 5;

    private Repository $cache;

    private RateLimiter $limiter;

    public function __construct(private SmsSenderService $smsSender)
    {
        // Store fixo no banco central (funciona com ou sem tenancy inicializado)
        $this->cache = Cache::store('central');
        $this->limiter = new RateLimiter($this->cache);
    }

    /**
     * @return array{sent: bool, error?: string, retry_after?: int}
     */
    public function sendCode(string $formId, string $phone, string $tenantId, string $ip): array
    {
        $resendKey = "phone-otp-resend:{$phone}";
        if ($this->limiter->tooManyAttempts($resendKey, 1)) {
            return ['sent' => false, 'error' => 'Já enviamos um código para este número. Use o código recebido ou aguarde para reenviar.', 'retry_after' => $this->limiter->availableIn($resendKey)];
        }

        $hourKey = "phone-otp-hour:{$phone}";
        $ipKey = "phone-otp-ip:{$ip}";
        if ($this->limiter->tooManyAttempts($hourKey, self::MAX_SENDS_PER_HOUR) || $this->limiter->tooManyAttempts($ipKey, self::MAX_SENDS_PER_HOUR * 3)) {
            return ['sent' => false, 'error' => 'Limite de códigos atingido. Tente novamente mais tarde.', 'retry_after' => $this->limiter->availableIn($hourKey)];
        }

        $code = (string) random_int(100000, 999999);

        $result = $this->smsSender->send(
            $phone,
            "Seu código de verificação é {$code}. Válido por ".self::CODE_TTL_MINUTES.' minutos. Não compartilhe.',
            $tenantId,
        );

        if (! $result['sent']) {
            Log::warning('Falha ao enviar código de verificação de telefone', ['phone' => $phone, 'error' => $result['error'] ?? null]);

            return ['sent' => false, 'error' => 'Não foi possível enviar o SMS para este número. Confira o número e tente novamente.'];
        }

        $this->cache->put($this->codeKey($formId, $phone), [
            'hash' => $this->hash($code),
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        $this->limiter->hit($resendKey, self::RESEND_SECONDS);
        $this->limiter->hit($hourKey, 3600);
        $this->limiter->hit($ipKey, 3600);

        if (app()->isLocal()) {
            Log::debug("Código de verificação para {$phone}: {$code}");
        }

        return ['sent' => true, 'retry_after' => self::RESEND_SECONDS];
    }

    /**
     * @return array{verified: bool, token?: string, error?: string}
     */
    public function verifyCode(string $formId, string $phone, string $code): array
    {
        $key = $this->codeKey($formId, $phone);
        $data = $this->cache->get($key);

        if (! $data) {
            return ['verified' => false, 'error' => 'Código expirado ou não solicitado. Peça um novo código.'];
        }

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $this->cache->forget($key);

            return ['verified' => false, 'error' => 'Muitas tentativas incorretas. Peça um novo código.'];
        }

        if (! hash_equals($data['hash'], $this->hash($code))) {
            $data['attempts']++;
            $this->cache->put($key, $data, now()->addMinutes(self::CODE_TTL_MINUTES));
            $restantes = self::MAX_ATTEMPTS - $data['attempts'];

            return ['verified' => false, 'error' => $restantes > 0
                ? "Código incorreto. Você tem mais {$restantes} tentativa(s)."
                : 'Muitas tentativas incorretas. Peça um novo código.'];
        }

        $this->cache->forget($key);
        $token = Str::random(40);
        $this->cache->put($this->tokenKey($formId, $phone), $this->hash($token), now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return ['verified' => true, 'token' => $token];
    }

    public function isVerified(string $formId, string $phone, ?string $token): bool
    {
        $stored = $this->cache->get($this->tokenKey($formId, $phone));

        return $token && $stored && hash_equals($stored, $this->hash($token));
    }

    // Token é de uso único: invalida após o envio do formulário
    public function consumeToken(string $formId, string $phone): void
    {
        $this->cache->forget($this->tokenKey($formId, $phone));
    }

    private function codeKey(string $formId, string $phone): string
    {
        return "phone-otp:{$formId}:{$phone}";
    }

    private function tokenKey(string $formId, string $phone): string
    {
        return "phone-verified:{$formId}:{$phone}";
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key'));
    }
}
