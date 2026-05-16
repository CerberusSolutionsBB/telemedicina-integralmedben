<?php
namespace App\Data;

class SiprovAssociadoData
{
    public function __construct(
        public readonly string $codigoIntegracao,
        public readonly string $nomePessoa,
        public readonly string $cpfCnpj,
        public readonly string $email,
        public readonly string $sexo,
        public readonly ?string $dataNascimento,
        public readonly array $telefones,
    ) {}

    public static function fromIntegrationData(SiprovIntegrationData $data): self
    {
        return new self(
            codigoIntegracao: $data->codigoIntegracao,
            nomePessoa: $data->nomePessoa,
            cpfCnpj: $data->cpfCnpj,
            email: $data->email,
            sexo: $data->sexo,
            dataNascimento: $data->dataNascimento,
            telefones: $data->telefones,
        );
    }

    public function toPayload(): array
    {
        $payload = [
            'codLoja'          => (int) config('siprov.cod_loja'),
            'codigoIntegracao' => $this->codigoIntegracao,
            'nomePessoa'       => $this->nomePessoa,
            'cpfCnpj'          => $this->cpfCnpj,
            'email'            => $this->email,
            'natureza'         => 'F',
            'sexo'             => $this->sexo,
            'recebeEmail'      => true,
            'recebeWhatsApp'   => true,
            'telefones'        => $this->telefones,
        ];

        if (! empty($this->dataNascimento)) {
            $payload['dataNascimento'] = $this->dataNascimento;
        }

        return $payload;
    }
}