<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZougSmsService
{
    private string $baseUrl;

    private string $user;

    private string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.zoug.url');
        $this->user = config('services.zoug.user');
        $this->password = config('services.zoug.password');
    }

    /**
     * Envia SMS via API Zoug
     *
     * @param  string  $telefone  Número do destinatário (com ou sem formatação)
     * @param  string  $conteudo  Mensagem a ser enviada
     * @param  string|null  $referenceId  ID de referência opcional (gera UUID se null)
     * @return array{success: bool, message_id?: string, reference_id?: string, error?: string}
     */
    public function enviar(string $telefone, string $conteudo, ?string $referenceId = null): array
    {
        $destinationAddr = $this->formatarTelefone($telefone);
        $credentials = base64_encode("{$this->user}:{$this->password}");

        // ✅ Gera UUID automaticamente se não fornecido
        $referenceId = $referenceId ?? Str::uuid()->toString();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '.$credentials,
            ])->post("{$this->baseUrl}/message", [
                'destination_addr' => $destinationAddr,
                'message' => $conteudo,
                'reference_id' => $referenceId,
            ]);

            $data = $response->json();

            if (! empty($data['error'])) {
                Log::warning('Zoug SMS erro', [
                    'telefone' => $destinationAddr,
                    'reference_id' => $referenceId,
                    'erro' => $data['message'] ?? 'Erro desconhecido',
                    'esme' => $data['esme'] ?? null,
                ]);

                return [
                    'success' => false,
                    'reference_id' => $referenceId,
                    'error' => $data['message'] ?? 'Erro ao enviar SMS',
                ];
            }

            return [
                'success' => true,
                'message_id' => $data['message_id'],
                'reference_id' => $referenceId, // ✅ Retorna para salvar no banco
            ];

        } catch (\Exception $e) {
            Log::error('Zoug SMS exceção', [
                'telefone' => $destinationAddr,
                'reference_id' => $referenceId,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reference_id' => $referenceId,
                'error' => 'Falha na comunicação com serviço SMS',
            ];
        }
    }

    /**
     * Envia SMS em lote (batch)
     *
     * @param  array  $mensagens  Array de ['telefone' => '...', 'conteudo' => '...', 'reference_id' => '...']
     * @return array{success: bool, batch_id?: string, error?: string}
     */
    public function enviarLote(array $mensagens): array
    {
        if (count($mensagens) > 400) {
            return [
                'success' => false,
                'error' => 'Limite de 400 mensagens por lote excedido',
            ];
        }

        $credentials = base64_encode("{$this->user}:{$this->password}");

        $list = array_map(function ($msg) {
            return [
                'destination_addr' => $this->formatarTelefone($msg['telefone']),
                'message' => $msg['conteudo'],
                'reference_id' => $msg['reference_id'] ?? Str::uuid()->toString(),
            ];
        }, $mensagens);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '.$credentials,
            ])->post("{$this->baseUrl}/message/batch", [
                'list' => $list,
            ]);

            $data = $response->json();

            if (! empty($data['error']) || $response->failed()) {
                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Erro no envio em lote',
                ];
            }

            return [
                'success' => true,
                'batch_id' => $data['batchId'],
            ];

        } catch (\Exception $e) {
            Log::error('Zoug SMS lote exceção', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Falha na comunicação com serviço SMS',
            ];
        }
    }

    /**
     * Formata o telefone adicionando +55 e removendo caracteres não numéricos
     */
    private function formatarTelefone(string $telefone): string
    {
        // Remove tudo que não for número
        $numeros = preg_replace('/\D/', '', $telefone);

        // Remove o 55 inicial se já existir (evita +5555...)
        $numeros = ltrim($numeros, '55');

        // Adiciona o prefixo +55
        return '+55'.$numeros;
    }
}
