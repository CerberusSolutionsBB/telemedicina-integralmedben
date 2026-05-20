<?php
namespace App\Data;

use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovAssociadoQueryData
{
    public function __construct(
        public readonly ?string $situacaoBeneficio = null,
        public readonly ?string $cpfCnpj = null,
        public readonly ?string $codigoIntegracao = null,
        public readonly ?string $nomePessoa = null,
    ) {}

    public static function fromSituacaoBeneficio(?string $situacaoBeneficio): self
    {
        return new self(situacaoBeneficio: $situacaoBeneficio);
    }

    public function toQueryParams(): array
    {
        try {
            $params = [];

            if (! empty($this->situacaoBeneficio)) {
                $params['situacaoBeneficio'] = $this->situacaoBeneficio;
            }

            if (! empty($this->cpfCnpj)) {
                $params['cpfCnpj'] = preg_replace('/\D/', '', $this->cpfCnpj);
            }

            if (! empty($this->codigoIntegracao)) {
                $params['codigoIntegracao'] = $this->codigoIntegracao;
            }

            if (! empty($this->nomePessoa)) {
                $params['nomePessoa'] = $this->nomePessoa;
            }

            Log::info('SIPROV | Query params associado montado', [
                'params' => $params,
            ]);

            return $params;

        } catch (Throwable $e) {
            Log::critical('SIPROV | Erro ao montar query params associado', [
                'message' => $e->getMessage(),
                'dto'     => [
                    'situacaoBeneficio' => $this->situacaoBeneficio,
                    'cpfCnpj'           => $this->cpfCnpj,
                    'codigoIntegracao'  => $this->codigoIntegracao,
                    'nomePessoa'        => $this->nomePessoa,
                ],
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
