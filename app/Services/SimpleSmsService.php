<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        Log::info('SMS | Enviando mensagem', [
            'phone_original' => $phone,
            'phone_formatted' => $destinationAddr,
            'message' => $message,
            'reference' => $ref,
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$this->auth,
        ])->withoutVerifying()->post("{$this->baseUrl}/message", [
            'destination_addr' => $destinationAddr,
            'message' => $message,
            'reference_id' => 'teste-001',
        ]);

        $data = $response->json();

        $result = [
            'sent' => empty($data['error']),
            'message_id' => $data['message_id'] ?? null,
            'error' => $data['message'] ?? null,
        ];

        if ($result['sent']) {
            Log::info('SMS | Enviado com sucesso', [
                'phone' => $destinationAddr,
                'message_id' => $result['message_id'],
            ]);
        } else {
            Log::error('SMS | Erro ao enviar', [
                'phone' => $destinationAddr,
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
}
