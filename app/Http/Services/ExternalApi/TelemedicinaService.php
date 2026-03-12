<?php

namespace App\Http\Services\ExternalApi;

use App\Interfaces\ExternalApiInterface;
use Illuminate\Support\Facades\Http;

class TelemedicinaService implements ExternalApiInterface
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
    ) {}

    /**
     * Registra o paciente na API de telemedicina.
     * Só deve ser chamado quando o tenant tem a pergunta de plano configurada
     * e o paciente selecionou um plano.
     * TODO: implementar após receber a documentação da API.
     */
    public function registerPatient(array $data): array
    {
        throw new \RuntimeException('TelemedicinaService: implementação pendente — aguardando documentação da API.');
    }
}
