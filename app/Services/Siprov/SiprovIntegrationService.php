<?php
namespace App\Services\Siprov;

use App\Data\SiprovAssociadoData;
use App\Data\SiprovBeneficioData;
use App\Data\SiprovIntegrationData;

class SiprovIntegrationService
{
    public function __construct(
        private readonly SiprovAssociadoService $associadoService,
        private readonly SiprovBeneficioService $beneficioService,
    ) {}

    public function execute(SiprovIntegrationData $data): array
    {
        $associadoData = SiprovAssociadoData::fromIntegrationData($data);
        $beneficioData = SiprovBeneficioData::fromIntegrationData($data);

        $associado = $this->associadoService->create($associadoData);

        $beneficio = $this->beneficioService->create($beneficioData);

        return [
            'associado' => $associado,
            'beneficio' => $beneficio,
        ];
    }
}