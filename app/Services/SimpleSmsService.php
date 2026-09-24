<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SimpleSmsService
{
    private const ENCODING_GSM7 = '0';

    private const ENCODING_LATIN1 = '3';

    private const ENCODING_UCS2 = '8';

    private const GSM7_CHARS = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà^{}\\[~]|€";

    private const HTTP_ERRORS = [
        400 => 'Requisição inválida',
        401 => 'Token inválido ou cliente não encontrado',
        402 => 'Saldo insuficiente',
        403 => 'Cliente inativo ou fora do horário/dia permitido',
        404 => 'Rota não pertence ao cliente',
        422 => 'Validação falhou',
        429 => 'Limite de TPS excedido',
        500 => 'Erro interno no provider',
    ];

    private string $baseUrl;

    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.devyx.url', 'https://endpoint.devyx.com.br'), '/');
        $this->token = config('services.devyx.token');
    }

    public function send(string $phone, string $message, ?string $ref = null): array
    {
        if (empty($this->token)) {
            Log::error('SMS | DEVYX_SMS_TOKEN não configurado');

            return [
                'sent' => false,
                'message_id' => null,
                'error' => 'Token da API de SMS (DEVYX_SMS_TOKEN) não configurado.',
            ];
        }

        $destinationAddr = $this->formatPhone($phone);
        $shortenedMessage = $this->shortenUrls($message);

        $payload = array_filter([
            'type' => 'SMS',
            'message' => $shortenedMessage,
            'encoding' => $this->detectEncoding($shortenedMessage),
            'destination_addr' => $destinationAddr,
            'reference_id' => $ref ?? (string) Str::uuid(),
            'source_addr' => config('services.devyx.source_addr'),
            'callback_url' => config('services.devyx.callback_url'),
            'callback_level' => config('services.devyx.callback_level'),
        ], fn ($value) => $value !== null && $value !== '');

        Log::info('SMS | Enviando mensagem', [
            'phone_original' => $phone,
            'phone_formatted' => $destinationAddr,
            'message_original_length' => mb_strlen($message),
            'message_shortened_length' => mb_strlen($shortenedMessage),
            'encoding' => $payload['encoding'],
            'message' => $shortenedMessage,
            'reference' => $payload['reference_id'],
        ]);

        $response = Http::withHeaders([
            'X-API-TOKEN' => $this->token,
        ])->acceptJson()->asJson()->timeout(30)->post("{$this->baseUrl}/api/send", $payload);

        $data = $response->json() ?? [];

        Log::info('SMS | Resposta Devyx', [
            'status_code' => $response->status(),
            'response' => $data,
        ]);

        $sent = $response->successful() && ! empty($data['message_id']);

        $result = [
            'sent' => $sent,
            'message_id' => $data['message_id'] ?? null,
            'error' => $sent ? null : $this->errorMessage($response->status(), $data),
        ];

        if ($result['sent']) {
            Log::info('SMS | Enviado com sucesso', [
                'phone' => $phone,
                'message_id' => $result['message_id'],
            ]);
        } else {
            Log::error('SMS | Erro ao enviar', [
                'phone' => $phone,
                'error' => $result['error'],
                'status' => $response->status(),
                'response' => $data,
            ]);
        }

        return $result;
    }

    /**
     * A API exige 11-13 dígitos: normaliza para 55 + DDD + número.
     */
    private function formatPhone(string $phone): string
    {
        $numbers = preg_replace('/\D/', '', $phone);

        if (strlen($numbers) <= 11) {
            $numbers = '55'.ltrim($numbers, '0');
        }

        return $numbers;
    }

    private function detectEncoding(string $message): string
    {
        $isGsm7 = collect(mb_str_split($message))
            ->every(fn ($char) => mb_strpos(self::GSM7_CHARS, $char) !== false);

        if ($isGsm7) {
            return self::ENCODING_GSM7;
        }

        $isLatin1 = mb_convert_encoding(mb_convert_encoding($message, 'ISO-8859-1', 'UTF-8'), 'UTF-8', 'ISO-8859-1') === $message;

        return $isLatin1 ? self::ENCODING_LATIN1 : self::ENCODING_UCS2;
    }

    private function errorMessage(int $status, array $data): string
    {
        $providerError = $data['error'] ?? $data['message'] ?? $data['status'] ?? null;

        if (! empty($data['details'])) {
            $providerError .= ' '.json_encode($data['details'], JSON_UNESCAPED_UNICODE);
        }

        $description = self::HTTP_ERRORS[$status] ?? "HTTP {$status}";

        return $providerError ? "{$description}: {$providerError}" : $description;
    }

    private function shortenUrls(string $message): string
    {
        return preg_replace_callback(
            '/(https?:\/\/[^\s]{20,})/i',
            fn ($matches) => $this->getShortUrl($matches[1]),
            $message
        );
    }

    private function getShortUrl(string $url): string
    {
        try {
            $response = Http::timeout(5)->get('https://tinyurl.com/api-create.php', [
                'url' => $url,
            ]);

            if ($response->successful() && $response->body() !== 'Error') {
                $shortUrl = $response->body();

                Log::info('SMS | URL encurtada', [
                    'original' => $url,
                    'short' => $shortUrl,
                ]);

                return $shortUrl;
            }
        } catch (\Exception $e) {
            Log::warning('SMS | Erro ao encurtar URL', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return $url;
    }
}
