<?php 

namespace App\Http\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsService {

    public function send($numeroDestinatario, $mensagem) {
        $response = Http::withBasicAuth(
            config('sms.user'),
            config('sms.password')
        )
        ->baseUrl(config('sms.base_url'))
        ->asJson()
        ->post('/message', [
            'destination_addr' => preg_replace('/\D/', '', $numeroDestinatario),
            'message'          => $mensagem,
            'reference_id'     => (string) Str::uuid(),
        ]);

        $response->throw();

        return $response->json();
    }
}