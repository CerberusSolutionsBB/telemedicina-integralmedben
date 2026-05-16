<?php
namespace App\Data;

use App\Http\Requests\Siprov\CreateSiprovIntegrationRequest;

class SiprovIntegrationData
{
    public function __construct(
        public readonly string $codigoIntegracao,
        public readonly string $nomePessoa,
        public readonly string $cpfCnpj,
        public readonly string $email,
        public readonly string $sexo,
        public readonly ?string $dataNascimento,
        public readonly array $telefones,
        public readonly string $plano,
        public readonly bool $ativo,
        public readonly int $diaVencimento,
        public readonly string $situacao,
    ) {}

    public static function fromRequest(CreateSiprovIntegrationRequest $request): self
    {
        return new self(
            codigoIntegracao: $request->string('codigoIntegracao')->toString(),
            nomePessoa: $request->string('nomePessoa')->toString(),
            cpfCnpj: $request->string('cpfCnpj')->toString(),
            email: $request->string('email')->toString(),
            sexo: $request->string('sexo')->toString(),
            dataNascimento: $request->input('dataNascimento'),
            telefones: $request->input('telefones', []),
            plano: $request->string('plano')->toString(),
            ativo: $request->boolean('ativo', true),
            diaVencimento: (int) $request->input('diaVencimento', 10),
            situacao: $request->input('situacao', 'Ativo'),
        );
    }

    public function codPlano(): int
    {
        return (int) config('siprov.planos.' . $this->plano);
    }
}