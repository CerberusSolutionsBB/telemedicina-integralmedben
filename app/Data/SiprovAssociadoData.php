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
            codigoIntegracao: $data->codigoIntegracao ?: 'USR-' . self::onlyNumbers($data->cpfCnpj),
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
            'codigoIntegracao' => $this->codigoIntegracao ?: 'USR-' . $this->onlyNumbers($this->cpfCnpj),
            'nomePessoa'       => $this->nomePessoa,
            'cpfCnpj'          => $this->onlyNumbers($this->cpfCnpj),
            'email'            => $this->email,
            'natureza'         => 'F',
            'sexo'             => $this->normalizeSexo(),
            'recebeEmail'      => true,
            'recebeWhatsApp'   => true,
            'telefones'        => $this->normalizeTelefones(),
        ];

        if (! empty($this->dataNascimento)) {
            $payload['dataNascimento'] = $this->dataNascimento;
        }

        return $payload;
    }

    private function normalizeSexo(): string
    {
        return match ($this->sexo) {
            'M', 'Masculino' => 'Masculino',
            'F', 'Feminino'  => 'Feminino',
            'I', 'Outro'     => 'Outro',
            default => 'Outro',
        };
    }

    private function normalizeTelefones(): array
    {
        return collect($this->telefones)
            ->filter(fn($telefone) => ! empty($telefone['numero']))
            ->map(function ($telefone) {
                return [
                    'ddi'    => (int) ($telefone['ddi'] ?? 55),
                    'numero' => $this->onlyNumbers($telefone['numero']),
                    'tipo'   => $telefone['tipo'] ?? 'Celular',
                ];
            })
            ->values()
            ->toArray();
    }

    private static function onlyNumbers(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '');
    }
}
