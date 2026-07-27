<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SimpleSmsService
{
    private string $baseUrl;

    private string $auth;

    public function __construct()
    {
        $this->baseUrl = config('services.zoug.url', 'https://sms1.zoug.net.br');
        $this->auth = base64_encode(
            config('services.zoug.user').':'.config('services.zoug.password')
        );
    }

    public function send(string $phone, string $message, ?string $ref = null): array
    {
        $destinationAddr = $this->formatPhone($phone);
        $shortenedMessage = $this->shortenUrls($message);

        Log::info('SMS | Enviando mensagem', [
            'phone_original' => $phone,
            'phone_formatted' => $destinationAddr,
            'message_original_length' => strlen($message),
            'message_shortened_length' => strlen($shortenedMessage),
            'message' => $shortenedMessage,
            'reference' => $ref,
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$this->auth,
        ])->withoutVerifying()->post("{$this->baseUrl}/message", [
            'destination_addr' => $destinationAddr,
            'message' => $shortenedMessage,
            'reference_id' => $ref ?? (string) Str::uuid(),
        ]);

        $data = $response->json();

        Log::info('SMS | Resposta Zoug', [
            'status_code' => $response->status(),
            'response' => $data,
        ]);

        $result = [
            'sent' => $response->successful()
                && empty($data['error'])
                && ! empty($data['message_id']),
            'message_id' => $data['message_id'] ?? null,
            'error' => $data['message'] ?? $data['error'] ?? null,
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

    private function formatPhone(string $phone): string
    {
        $numbers = preg_replace('/\D/', '', $phone);
        $numbers = ltrim($numbers, '55');

        return '55'.$numbers;
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
