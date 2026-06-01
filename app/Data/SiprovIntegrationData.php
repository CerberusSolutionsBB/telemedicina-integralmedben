<?php

namespace App\Data;

use App\Http\Requests\Siprov\CreateSiprovIntegrationRequest;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class SiprovIntegrationData
{
    public function __construct(
        public readonly string $codigoIntegracao,
        public readonly string $nomePessoa,
        public readonly string $cpfCnpj,
        public readonly string $email,
        public readonly string $sexo,
        public readonly string $dataNascimento,
        public readonly array $telefones,
        public readonly string $plano,
        public readonly bool $ativo,
        public readonly int $diaVencimento,
        public readonly string $situacao,
    ) {}

    public static function fromRequest(CreateSiprovIntegrationRequest $request): self
    {
        try {
            $cpfCnpj = self::onlyNumbers($request->input('cpfCnpj'));

            $data = new self(
                codigoIntegracao: 'USR-'.$cpfCnpj,
                nomePessoa: $request->string('nomePessoa')->toString(),
                cpfCnpj: $cpfCnpj,
                email: $request->string('email')->toString(),
                sexo: $request->string('sexo')->toString(),
                dataNascimento: $request->input('dataNascimento'),
                telefones: $request->input('telefones', []),
                plano: $request->string('plano')->toString(),
                ativo: $request->boolean('ativo', true),
                diaVencimento: (int) $request->input('diaVencimento', 10),
                situacao: $request->input('situacao', 'Ativo'),
            );

            Log::info('SIPROV | DTO integração criado', [
                'codigoIntegracao' => $data->codigoIntegracao,
                'cpfCnpj' => $data->cpfCnpj,
                'plano' => $data->plano,
                'codPlano' => $data->codPlano(),
            ]);

            return $data;
        } catch (Throwable $e) {
            Log::error('SIPROV | Erro ao criar DTO integração', [
                'message' => $e->getMessage(),
                'request' => $request->except([
                    'password',
                    'token',
                    '_token',
                ]),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    public function codPlano(): int
    {
        $codPlano = config('siprov.planos.'.$this->plano);

        if (! $codPlano) {
            Log::error('SIPROV | Plano inválido ou não configurado', [
                'plano' => $this->plano,
                'planos_configurados' => config('siprov.planos'),
            ]);

            throw new InvalidArgumentException("Plano SIPROV inválido: {$this->plano}");
        }

        return (int) $codPlano;
    }

    private static function onlyNumbers(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '');
    }
}
