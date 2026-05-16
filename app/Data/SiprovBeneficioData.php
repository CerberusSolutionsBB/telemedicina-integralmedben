<?php
namespace App\Data;

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
        return new self(
            ativo: $data->ativo,
            codPlano: $data->codPlano(),
            cpfCnpj: $data->cpfCnpj,
            diaVencimento: $data->diaVencimento,
            situacao: $data->situacao,
        );
    }

    public function toPayload(): array
    {
        return [
            'ativo'         => $this->ativo,
            'codLoja'       => (int) config('siprov.cod_loja'),
            'codPlano'      => $this->codPlano,
            'cpfCnpj'       => $this->cpfCnpj,
            'diaVencimento' => $this->diaVencimento,
            'situacao'      => $this->situacao,
        ];
    }
}