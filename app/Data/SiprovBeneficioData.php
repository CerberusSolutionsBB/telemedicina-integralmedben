<?php

namespace App\Data;

use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovBeneficioData
{
    public function __construct(
        public readonly bool $ativo,
        public readonly int $codPlano,
        public readonly string $cpfCnpj,
        public readonly int $diaVencimento,
        public readonly string $situacao,
    ) {}

    public static function fromIntegrationData(SiprovIntegrationData $data): self
    {
        try {
            $instance = new self(
                ativo: $data->ativo,
                codPlano: $data->codPlano(),
                cpfCnpj: $data->cpfCnpj,
                diaVencimento: $data->diaVencimento,
                situacao: $data->situacao,
            );

            Log::info('SIPROV | DTO benefício criado', [
                'ativo' => $instance->ativo,
                'codPlano' => $instance->codPlano,
                'cpfCnpj' => $instance->cpfCnpj,
                'diaVencimento' => $instance->diaVencimento,
                'situacao' => $instance->situacao,
            ]);

            return $instance;

        } catch (Throwable $e) {
            Log::error('SIPROV | Erro ao criar DTO de benefício', [
                'message' => $e->getMessage(),
                'data' => [
                    'ativo' => $data->ativo ?? null,
                    'plano' => $data->plano ?? null,
                    'cpfCnpj' => $data->cpfCnpj ?? null,
                    'diaVencimento' => $data->diaVencimento ?? null,
                    'situacao' => $data->situacao ?? null,
                ],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    public function toPayload(): array
    {
        try {
            $payload = [
                'ativo' => $this->ativo,
                'codLoja' => (int) config('siprov.cod_loja'),
                'codPlano' => $this->codPlano,
                'cpfCnpj' => $this->cpfCnpj,
                'diaVencimento' => $this->diaVencimento,
                'situacao' => $this->situacao,
            ];

            Log::info('SIPROV | Payload benefício montado', [
                'payload' => $payload,
            ]);

            return $payload;

        } catch (Throwable $e) {
            Log::critical('SIPROV | Erro ao montar payload benefício', [
                'message' => $e->getMessage(),
                'dto' => [
                    'ativo' => $this->ativo,
                    'codPlano' => $this->codPlano,
                    'cpfCnpj' => $this->cpfCnpj,
                    'diaVencimento' => $this->diaVencimento,
                    'situacao' => $this->situacao,
                ],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
